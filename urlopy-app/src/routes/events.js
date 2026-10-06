const express = require('express');
const { db, persist, id } = require('../db');
const { requireAuth } = require('../middleware/auth');
const { sendMail } = require('../mailer');
const { EVENT_TYPES } = require('../eventTypes');

const router = express.Router();

function visibleEventsFor(user) {
  if (user.role === 'admin') return db.events;
  return db.events.filter((e) => e.groupId === user.groupId || e.userId === user.id);
}

function typeLabel(value) {
  const t = EVENT_TYPES.find((t) => t.value === value);
  return t ? t.label : value;
}

router.get('/', requireAuth, (req, res) => {
  res.render('calendar', { eventTypes: EVENT_TYPES });
});

router.get('/api/events', requireAuth, (req, res) => {
  const events = visibleEventsFor(req.currentUser).map((e) => {
    const owner = db.users.find((u) => u.id === e.userId);
    return {
      id: e.id,
      title: `${owner ? owner.name : '?'} - ${typeLabel(e.type)}${e.note ? ' (' + e.note + ')' : ''}`,
      start: e.startDate,
      end: addOneDay(e.endDate),
      color: owner ? owner.color : '#64748b',
      allDay: true,
      extendedProps: {
        userId: e.userId,
        userName: owner ? owner.name : '?',
        type: e.type,
        typeLabel: typeLabel(e.type),
        note: e.note || '',
        canDelete: req.currentUser.role === 'admin' || req.currentUser.id === e.userId,
      },
    };
  });
  res.json(events);
});

function addOneDay(dateStr) {
  // Uwaga: celowo NIE używamy toISOString() tutaj - ono konwertuje do UTC,
  // co w strefach na wschód od UTC (np. Polska) przesuwałoby date o 1 dzień
  // wstecz (np. gdy serwer Node działa w lokalnej strefie czasowej).
  const d = new Date(dateStr + 'T00:00:00');
  d.setDate(d.getDate() + 1);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

router.post('/events', requireAuth, async (req, res) => {
  const { startDate, endDate, type, note } = req.body;
  if (!startDate || !endDate || !type) {
    return res.status(400).json({ error: 'Wypełnij wymagane pola.' });
  }
  if (endDate < startDate) {
    return res.status(400).json({ error: 'Data zakończenia nie może być wcześniejsza niz data rozpoczęcia.' });
  }
  const event = {
    id: id(),
    userId: req.currentUser.id,
    groupId: req.currentUser.groupId,
    startDate,
    endDate,
    type,
    note: (note || '').trim().slice(0, 500),
    createdAt: new Date().toISOString(),
  };
  db.events.push(event);
  persist();

  const groupMembers = db.users.filter(
    (u) => u.groupId === req.currentUser.groupId && u.id !== req.currentUser.id
  );
  const recipients = groupMembers.map((u) => u.email).filter(Boolean);
  sendMail({
    to: recipients,
    subject: `Nowe zdarzenie urlopowe: ${req.currentUser.name}`,
    text: [
      `${req.currentUser.name} dodal(a) nowy wpis w kalendarzu urlopów.`,
      ``,
      `Typ: ${typeLabel(type)}`,
      `Od: ${startDate}`,
      `Do: ${endDate}`,
      event.note ? `Notatka: ${event.note}` : '',
    ]
      .filter(Boolean)
      .join('\n'),
  });

  res.json({ ok: true });
});

router.post('/events/:id/delete', requireAuth, (req, res) => {
  const event = db.events.find((e) => e.id === req.params.id);
  if (!event) return res.status(404).json({ error: 'Nie znaleziono zdarzenia.' });
  if (req.currentUser.role !== 'admin' && event.userId !== req.currentUser.id) {
    return res.status(403).json({ error: 'Brak uprawnień do usunięcia tego zdarzenia.' });
  }
  db.events = db.events.filter((e) => e.id !== req.params.id);
  persist();
  res.json({ ok: true });
});

module.exports = router;

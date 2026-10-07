const express = require('express');
const bcrypt = require('bcryptjs');
const { db, persist, id } = require('../db');
const { requireAuth, requireAdmin } = require('../middleware/auth');

const router = express.Router();
router.use('/admin', requireAuth, requireAdmin);

function usersWithGroupName() {
  return db.users.map((u) => ({
    ...u,
    groupName: (db.groups.find((g) => g.id === u.groupId) || {}).name || '-',
  }));
}

// --- Grupy (działy) ---

router.get('/admin/groups', (req, res) => {
  res.render('admin_groups', { groups: db.groups, error: null });
});

router.post('/admin/groups', (req, res) => {
  const name = (req.body.name || '').trim();
  if (!name) {
    return res.status(400).render('admin_groups', { groups: db.groups, error: 'Nazwa działu jest wymagana.' });
  }
  db.groups.push({ id: id(), name });
  persist();
  res.redirect('/admin/groups');
});

router.post('/admin/groups/:id/delete', (req, res) => {
  const inUse = db.users.some((u) => u.groupId === req.params.id);
  if (inUse) {
    return res.status(400).render('admin_groups', {
      groups: db.groups,
      error: 'Nie można usunąć działu, który ma przypisanych użytkowników.',
    });
  }
  db.groups = db.groups.filter((g) => g.id !== req.params.id);
  persist();
  res.redirect('/admin/groups');
});

// --- Użytkownicy ---

router.get('/admin/users', (req, res) => {
  res.render('admin_users', { users: usersWithGroupName(), groups: db.groups, error: null });
});

router.post('/admin/users', (req, res) => {
  const { name, email, password, role, groupId, color } = req.body;
  const cleanEmail = (email || '').trim().toLowerCase();
  if (!name || !cleanEmail || !password || !groupId) {
    return res.status(400).render('admin_users', {
      users: usersWithGroupName(),
      groups: db.groups,
      error: 'Wypełnij wszystkie wymagane pola.',
    });
  }
  if (db.users.some((u) => u.email.toLowerCase() === cleanEmail)) {
    return res.status(400).render('admin_users', {
      users: usersWithGroupName(),
      groups: db.groups,
      error: 'Użytkownik z tym adresem e-mail już istnieje.',
    });
  }
  db.users.push({
    id: id(),
    name: name.trim(),
    email: cleanEmail,
    passwordHash: bcrypt.hashSync(password, 10),
    role: role === 'admin' ? 'admin' : 'user',
    groupId,
    color: /^#[0-9a-fA-F]{6}$/.test(color || '') ? color : '#2563eb',
    mustChangePassword: true,
  });
  persist();
  res.redirect('/admin/users');
});

router.post('/admin/users/:id', (req, res) => {
  const user = db.users.find((u) => u.id === req.params.id);
  if (!user) return res.redirect('/admin/users');
  const { name, role, groupId, password } = req.body;
  if (name) user.name = name.trim();
  if (groupId) user.groupId = groupId;
  user.role = role === 'admin' ? 'admin' : 'user';
  if (password) {
    user.passwordHash = bcrypt.hashSync(password, 10);
    user.mustChangePassword = true;
  }
  persist();
  res.redirect('/admin/users');
});

router.post('/admin/users/:id/delete', (req, res) => {
  if (req.params.id === req.currentUser.id) {
    return res.status(400).render('admin_users', {
      users: usersWithGroupName(),
      groups: db.groups,
      error: 'Nie można usunąć własnego konta.',
    });
  }
  db.users = db.users.filter((u) => u.id !== req.params.id);
  db.events = db.events.filter((e) => e.userId !== req.params.id);
  persist();
  res.redirect('/admin/users');
});

module.exports = router;

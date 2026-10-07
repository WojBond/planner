const express = require('express');
const bcrypt = require('bcryptjs');
const { db, persist } = require('../db');
const { requireAuth } = require('../middleware/auth');

const router = express.Router();

router.get('/profile', requireAuth, (req, res) => {
  const group = db.groups.find((g) => g.id === req.currentUser.groupId) || null;
  res.render('profile', { group, saved: false, error: null });
});

router.post('/profile/color', requireAuth, (req, res) => {
  const { color } = req.body;
  if (!/^#[0-9a-fA-F]{6}$/.test(color || '')) {
    const group = db.groups.find((g) => g.id === req.currentUser.groupId) || null;
    return res.status(400).render('profile', { group, saved: false, error: 'Nieprawidłowy format koloru.' });
  }
  req.currentUser.color = color;
  persist();
  const group = db.groups.find((g) => g.id === req.currentUser.groupId) || null;
  res.render('profile', { group, saved: true, error: null });
});

router.post('/profile/password', requireAuth, (req, res) => {
  const { currentPassword, newPassword } = req.body;
  const group = db.groups.find((g) => g.id === req.currentUser.groupId) || null;
  if (!bcrypt.compareSync(currentPassword || '', req.currentUser.passwordHash)) {
    return res.status(400).render('profile', { group, saved: false, error: 'Aktualne hasło jest nieprawidłowe.' });
  }
  if (!newPassword || newPassword.length < 6) {
    return res.status(400).render('profile', { group, saved: false, error: 'Nowe hasło musi miec co najmniej 6 znaków.' });
  }
  req.currentUser.passwordHash = bcrypt.hashSync(newPassword, 10);
  req.currentUser.mustChangePassword = false;
  persist();
  res.render('profile', { group, saved: true, error: null });
});

module.exports = router;

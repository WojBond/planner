const express = require('express');
const bcrypt = require('bcryptjs');
const { db } = require('../db');

const router = express.Router();

router.get('/login', (req, res) => {
  if (req.currentUser) return res.redirect('/');
  res.render('login', { error: null });
});

router.post('/login', (req, res) => {
  const { email, password } = req.body;
  const user = db.users.find(
    (u) => u.email.toLowerCase() === String(email || '').toLowerCase()
  );
  if (!user || !bcrypt.compareSync(password || '', user.passwordHash)) {
    return res.status(401).render('login', { error: 'Nieprawidlowy e-mail lub haslo.' });
  }
  req.session.userId = user.id;
  res.redirect('/');
});

router.post('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/login'));
});

module.exports = router;

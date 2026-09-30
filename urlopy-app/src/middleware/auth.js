const { db } = require('../db');

function currentUser(req) {
  if (!req.session || !req.session.userId) return null;
  return db.users.find((u) => u.id === req.session.userId) || null;
}

function attachUser(req, res, next) {
  req.currentUser = currentUser(req);
  res.locals.currentUser = req.currentUser;
  next();
}

function requireAuth(req, res, next) {
  if (!req.currentUser) return res.redirect('/login');
  next();
}

function requireAdmin(req, res, next) {
  if (!req.currentUser || req.currentUser.role !== 'admin') {
    return res.status(403).render('error', { message: 'Brak uprawnien administratora.' });
  }
  next();
}

module.exports = { attachUser, requireAuth, requireAdmin };

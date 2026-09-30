<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

$manageLink = $user['role'] === 'admin' ? 'admin_users.php' : 'profile.php';

$pageTitle = 'Kalendarz - Planer urlopow';
$containerClass = 'container wide';
include __DIR__ . '/includes/header.php';
?>
<script src="assets/vendor/fullcalendar.min.js"></script>

<div class="cal-app">
  <aside class="cal-sidebar">
    <div class="sidebar-section">
      <h3>Kalendarze</h3>
      <ul class="calendar-list" id="calendarList">
        <li class="muted">Wczytywanie...</li>
      </ul>
      <a href="<?= e($manageLink) ?>" class="sidebar-link">+ Dodaj / Edytuj</a>
    </div>
    <div class="sidebar-section">
      <h3>Filtruj wg typu</h3>
      <ul class="type-filter-list" id="typeFilterList">
        <?php foreach ($GLOBALS['EVENT_TYPES'] as $value => $label): ?>
          <li>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
              <input type="checkbox" class="type-toggle" value="<?= e($value) ?>" checked />
              <?= e($label) ?>
            </label>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </aside>

  <div class="cal-main">
    <div class="cal-toolbar">
      <div class="cal-nav">
        <button type="button" id="navPrev" title="Poprzedni">&lsaquo;</button>
        <button type="button" id="navToday" class="wide">Dzis</button>
        <button type="button" id="navNext" title="Nastepny">&rsaquo;</button>
        <span class="year-label" id="navLabel"></span>
      </div>
      <div class="view-switch">
        <button type="button" data-view="month">Miesiac</button>
        <button type="button" data-view="year" class="active">Rok</button>
        <button type="button" data-view="list">Lista</button>
      </div>
      <button class="btn" id="btnAdd">+ Nowe zdarzenie</button>
    </div>

    <p class="muted" style="margin-top:-8px;">
      <?php if ($user['role'] === 'admin'): ?>
        Widzisz zdarzenia wszystkich uzytkownikow.
      <?php else: ?>
        Widzisz swoje zdarzenia oraz zdarzenia swojego dzialu.
      <?php endif; ?>
    </p>

    <div class="view-panel" id="panelMonth"><div id="calendar"></div></div>
    <div class="view-panel active" id="panelYear"><div class="year-grid" id="yearGrid"></div></div>
    <div class="view-panel" id="panelList"><div id="calendarList2"></div></div>
  </div>
</div>

<div class="modal-backdrop" id="modalAdd">
  <div class="modal">
    <h2>Nowe zdarzenie</h2>
    <div id="formError" class="alert error" style="display:none;"></div>
    <form id="eventForm">
      <div class="form-row">
        <label for="startDate">Od</label>
        <input type="date" id="startDate" name="startDate" required />
      </div>
      <div class="form-row">
        <label for="endDate">Do</label>
        <input type="date" id="endDate" name="endDate" required />
      </div>
      <div class="form-row">
        <label for="type">Typ</label>
        <select id="type" name="type" required>
          <?php foreach ($GLOBALS['EVENT_TYPES'] as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label for="note">Notatka (opcjonalnie)</label>
        <textarea id="note" name="note" rows="2"></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn secondary" id="btnCancel">Anuluj</button>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</div>

<div class="event-popover" id="eventPopover">
  <h4 id="popTitle"></h4>
  <p id="popDates"></p>
  <p>Kalendarz: <span class="pill" id="popCalendar"></span></p>
  <p id="popNoteWrap" style="display:none;"><strong>Notatka:</strong> <span id="popNote"></span></p>
  <div class="popover-actions">
    <button type="button" class="btn secondary" id="popClose">Zamknij</button>
    <button type="button" class="btn danger" id="popDelete" style="display:none;">Usun</button>
  </div>
</div>

<script>
const MONTH_NAMES = ['styczeń','luty','marzec','kwiecień','maj','czerwiec','lipiec','sierpień','wrzesień','październik','listopad','grudzień'];
const WEEKDAY_ABBR = ['niedz','pon','wt','śr','czw','pt','sob'];
const MONTH_ABBR = ['sty','lut','mar','kwi','maj','cze','lip','sie','wrz','paź','lis','gru'];

let allEvents = [];
let allCalendars = [];
let hiddenUsers = new Set();
let hiddenTypes = new Set();
let currentYear = new Date().getFullYear();
let currentView = 'year';

const modalAdd = document.getElementById('modalAdd');
const formError = document.getElementById('formError');

document.getElementById('btnAdd').addEventListener('click', () => {
  formError.style.display = 'none';
  document.getElementById('eventForm').reset();
  modalAdd.classList.add('open');
});
document.getElementById('btnCancel').addEventListener('click', () => modalAdd.classList.remove('open'));

document.getElementById('eventForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const params = new URLSearchParams({
    startDate: document.getElementById('startDate').value,
    endDate: document.getElementById('endDate').value,
    type: document.getElementById('type').value,
    note: document.getElementById('note').value,
  });
  const res = await fetch('events_create.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params.toString(),
  });
  const body = await res.json();
  if (!res.ok) {
    formError.textContent = body.error || 'Wystapil blad.';
    formError.style.display = 'block';
    return;
  }
  modalAdd.classList.remove('open');
  await loadData();
  refreshCurrentView();
});

// --- Popover ---
const popover = document.getElementById('eventPopover');
let selectedEventId = null;

function formatPl(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return `${WEEKDAY_ABBR[d.getDay()]} ${d.getDate()}. ${MONTH_ABBR[d.getMonth()]} ${d.getFullYear()}`;
}

function showPopover(ev, anchorRect) {
  selectedEventId = ev.id;
  document.getElementById('popTitle').textContent = ev.extendedProps.typeLabel;
  document.getElementById('popDates').textContent = formatPl(ev.start) + ' - ' + formatPl(ev.endInclusive);
  const pill = document.getElementById('popCalendar');
  pill.textContent = ev.extendedProps.userName;
  pill.style.background = ev.color;
  const noteWrap = document.getElementById('popNoteWrap');
  if (ev.extendedProps.note) {
    noteWrap.style.display = 'block';
    document.getElementById('popNote').textContent = ev.extendedProps.note;
  } else {
    noteWrap.style.display = 'none';
  }
  document.getElementById('popDelete').style.display = ev.extendedProps.canDelete ? 'inline-block' : 'none';

  popover.classList.add('open');
  const popRect = popover.getBoundingClientRect();
  let left = anchorRect.left;
  let top = anchorRect.bottom + 8;
  if (left + popRect.width > window.innerWidth - 12) left = window.innerWidth - popRect.width - 12;
  if (top + popRect.height > window.innerHeight - 12) top = anchorRect.top - popRect.height - 8;
  popover.style.left = Math.max(12, left) + 'px';
  popover.style.top = Math.max(12, top) + 'px';
}

document.getElementById('popClose').addEventListener('click', () => popover.classList.remove('open'));
document.getElementById('popDelete').addEventListener('click', async () => {
  if (!selectedEventId) return;
  if (!confirm('Usunac to zdarzenie?')) return;
  await fetch('events_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(selectedEventId),
  });
  popover.classList.remove('open');
  await loadData();
  refreshCurrentView();
});
document.addEventListener('click', (e) => {
  if (popover.classList.contains('open') && !popover.contains(e.target) && !e.target.closest('.year-event-bar')) {
    popover.classList.remove('open');
  }
});

// --- Dane ---
async function loadData() {
  const [calRes, evRes] = await Promise.all([
    fetch('api_calendars.php'),
    fetch('api_events.php'),
  ]);
  allCalendars = await calRes.json();
  const rawEvents = await evRes.json();
  allEvents = rawEvents.map(ev => ({
    ...ev,
    endInclusive: addDays(ev.end, -1),
  }));
}

function addDays(dateStr, delta) {
  const d = new Date(dateStr + 'T00:00:00');
  d.setDate(d.getDate() + delta);
  return d.toISOString().slice(0, 10);
}

function renderSidebar() {
  const list = document.getElementById('calendarList');
  list.innerHTML = '';
  allCalendars.forEach(cal => {
    const li = document.createElement('li');
    const domId = 'cal_' + cal.id;

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.id = domId;
    checkbox.checked = true;
    checkbox.addEventListener('change', (e) => {
      if (e.target.checked) hiddenUsers.delete(cal.id);
      else hiddenUsers.add(cal.id);
      refreshCurrentView();
    });

    const swatch = document.createElement('span');
    swatch.className = 'swatch';
    swatch.style.background = cal.color;

    const label = document.createElement('label');
    label.setAttribute('for', domId);
    label.style.cursor = 'pointer';
    label.textContent = cal.name;

    li.append(checkbox, swatch, label);
    list.appendChild(li);
  });

  document.querySelectorAll('.type-toggle').forEach(cb => {
    cb.addEventListener('change', (e) => {
      if (e.target.checked) hiddenTypes.delete(e.target.value);
      else hiddenTypes.add(e.target.value);
      refreshCurrentView();
    });
  });
}

function visibleEvents() {
  return allEvents.filter(ev =>
    !hiddenUsers.has(ev.extendedProps.userId) && !hiddenTypes.has(ev.extendedProps.type)
  );
}

// --- Widok roczny ---
function daysInMonth(year, month) { return new Date(year, month, 0).getDate(); }

function renderYear() {
  document.getElementById('navLabel').textContent = currentYear;
  const grid = document.getElementById('yearGrid');
  grid.innerHTML = '';

  const header = document.createElement('div');
  header.className = 'year-row year-header-row';
  const headerLabel = document.createElement('div');
  headerLabel.className = 'year-row-label';
  headerLabel.textContent = '';
  header.appendChild(headerLabel);
  const headerDays = document.createElement('div');
  headerDays.className = 'year-day-headers';
  for (let d = 1; d <= 31; d++) {
    const cell = document.createElement('div');
    cell.className = 'year-day-header';
    cell.textContent = d;
    headerDays.appendChild(cell);
  }
  header.appendChild(headerDays);
  grid.appendChild(header);

  const events = visibleEvents();

  for (let m = 1; m <= 12; m++) {
    const row = document.createElement('div');
    row.className = 'year-row';

    const label = document.createElement('div');
    label.className = 'year-row-label';
    label.textContent = MONTH_NAMES[m - 1];
    row.appendChild(label);

    const track = document.createElement('div');
    track.className = 'year-track';

    const cells = document.createElement('div');
    cells.className = 'year-day-cells';
    const dCount = daysInMonth(currentYear, m);
    for (let d = 1; d <= 31; d++) {
      const cell = document.createElement('div');
      cell.className = 'year-day-cell';
      if (d > dCount) {
        cell.classList.add('out-of-month');
      } else {
        const dow = new Date(currentYear, m - 1, d).getDay();
        if (dow === 0 || dow === 6) cell.classList.add('weekend');
      }
      cells.appendChild(cell);
    }
    track.appendChild(cells);

    const monthStart = new Date(currentYear, m - 1, 1);
    const monthEnd = new Date(currentYear, m - 1, dCount);
    const monthEvents = events
      .map(ev => {
        const s = new Date(ev.start + 'T00:00:00');
        const e = new Date(ev.endInclusive + 'T00:00:00');
        if (e < monthStart || s > monthEnd) return null;
        const startDay = s < monthStart ? 1 : s.getDate();
        const endDay = e > monthEnd ? dCount : e.getDate();
        return { ev, startDay, endDay };
      })
      .filter(Boolean)
      .sort((a, b) => a.startDay - b.startDay || (a.endDay - a.startDay) - (b.endDay - b.startDay));

    const laneEnds = [];
    monthEvents.forEach(item => {
      let lane = 0;
      while (laneEnds[lane] !== undefined && laneEnds[lane] >= item.startDay) lane++;
      laneEnds[lane] = item.endDay;
      item.lane = lane;
    });
    const laneCount = Math.max(1, laneEnds.length);
    const rowHeight = laneCount * 22 + 10;
    track.style.minHeight = rowHeight + 'px';
    row.style.minHeight = rowHeight + 'px';

    monthEvents.forEach(item => {
      const bar = document.createElement('div');
      bar.className = 'year-event-bar';
      const left = ((item.startDay - 1) / 31) * 100;
      const width = ((item.endDay - item.startDay + 1) / 31) * 100;
      bar.style.left = left + '%';
      bar.style.width = width + '%';
      bar.style.top = (item.lane * 22 + 5) + 'px';
      bar.style.background = item.ev.color;
      bar.textContent = item.ev.extendedProps.userName + ' - ' + item.ev.extendedProps.typeLabel;
      bar.addEventListener('click', (e) => {
        e.stopPropagation();
        showPopover(item.ev, bar.getBoundingClientRect());
      });
      track.appendChild(bar);
    });

    row.appendChild(track);
    grid.appendChild(row);
  }
}

// --- Widok miesiac / lista (FullCalendar) ---
function popoverDataFromFcEvent(fcEvent) {
  return {
    id: fcEvent.id,
    start: fcEvent.startStr,
    endInclusive: addDays(fcEvent.endStr, -1),
    color: fcEvent.backgroundColor,
    extendedProps: fcEvent.extendedProps,
  };
}

let fcCalendar = null;
function ensureFullCalendar() {
  if (fcCalendar) return fcCalendar;
  fcCalendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    height: 'auto',
    locale: 'pl',
    firstDay: 1,
    headerToolbar: false,
    initialView: 'dayGridMonth',
    events: (info, success) => success(visibleEvents()),
    eventClick: (info) => showPopover(popoverDataFromFcEvent(info.event), info.el.getBoundingClientRect()),
    datesSet: (info) => { document.getElementById('navLabel').textContent = info.view.title; },
  });
  fcCalendar.render();
  return fcCalendar;
}

let fcList = null;
function ensureFullCalendarList() {
  if (fcList) return fcList;
  fcList = new FullCalendar.Calendar(document.getElementById('calendarList2'), {
    height: 'auto',
    locale: 'pl',
    firstDay: 1,
    headerToolbar: false,
    initialView: 'listYear',
    initialDate: currentYear + '-01-01',
    events: (info, success) => success(visibleEvents()),
    eventClick: (info) => showPopover(popoverDataFromFcEvent(info.event), info.el.getBoundingClientRect()),
    datesSet: (info) => { document.getElementById('navLabel').textContent = info.view.title; },
  });
  fcList.render();
  return fcList;
}

function refreshCurrentView() {
  if (currentView === 'year') {
    renderYear();
  } else if (currentView === 'month') {
    ensureFullCalendar().refetchEvents();
  } else if (currentView === 'list') {
    ensureFullCalendarList().refetchEvents();
  }
}

function setView(view) {
  currentView = view;
  document.querySelectorAll('.view-switch button').forEach(b => b.classList.toggle('active', b.dataset.view === view));
  document.getElementById('panelMonth').classList.toggle('active', view === 'month');
  document.getElementById('panelYear').classList.toggle('active', view === 'year');
  document.getElementById('panelList').classList.toggle('active', view === 'list');
  popover.classList.remove('open');

  if (view === 'month') {
    ensureFullCalendar().updateSize();
  } else if (view === 'list') {
    ensureFullCalendarList().updateSize();
  }
  refreshCurrentView();
}

document.querySelectorAll('.view-switch button').forEach(btn => {
  btn.addEventListener('click', () => setView(btn.dataset.view));
});

document.getElementById('navPrev').addEventListener('click', () => {
  if (currentView === 'year') { currentYear--; renderYear(); }
  else if (currentView === 'month') { ensureFullCalendar().prev(); }
  else { currentYear--; ensureFullCalendarList().gotoDate(currentYear + '-01-01'); }
});
document.getElementById('navNext').addEventListener('click', () => {
  if (currentView === 'year') { currentYear++; renderYear(); }
  else if (currentView === 'month') { ensureFullCalendar().next(); }
  else { currentYear++; ensureFullCalendarList().gotoDate(currentYear + '-01-01'); }
});
document.getElementById('navToday').addEventListener('click', () => {
  if (currentView === 'year') { currentYear = new Date().getFullYear(); renderYear(); }
  else if (currentView === 'month') { ensureFullCalendar().today(); }
  else { currentYear = new Date().getFullYear(); ensureFullCalendarList().today(); }
});

(async function init() {
  await loadData();
  renderSidebar();
  renderYear();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

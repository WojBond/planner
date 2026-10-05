<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

$manageLink = $user['role'] === 'admin' ? 'admin_users.php' : 'profile.php';
$approvalEnabled = is_approval_enabled();

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
    <?php if ($approvalEnabled): ?>
    <div class="sidebar-section">
      <h3>Legenda</h3>
      <div class="muted" style="font-size:0.82rem; display:flex; flex-direction:column; gap:6px;">
        <span><span class="legend-swatch legend-pending"></span> Wprowadzony (oczekuje)</span>
        <span><span class="legend-swatch legend-approved"></span> Zatwierdzony</span>
      </div>
    </div>
    <?php endif; ?>
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
      <?php elseif ($user['role'] === 'kierownik'): ?>
        Widzisz zdarzenia swojego dzialu oraz dzialow, ktorymi zarzadzasz.
      <?php else: ?>
        Widzisz swoje zdarzenia oraz zdarzenia swojego dzialu.
      <?php endif; ?>
      Kliknij pusty dzien, aby dodac zdarzenie, albo istniejacy pasek, aby je edytowac.
      W widoku Rok mozesz tez przeciagac paski, aby przesunac urlop, albo ciagnac za ich brzegi, aby zmienic liczbe dni.
    </p>

    <div class="view-panel" id="panelMonth"><div id="calendar"></div></div>
    <div class="view-panel active" id="panelYear"><div class="year-grid" id="yearGrid"></div></div>
    <div class="view-panel" id="panelList"><div id="calendarList2"></div></div>
  </div>
</div>

<div class="modal-backdrop" id="modalAdd">
  <div class="modal">
    <h2 id="eventModalTitle">Nowe zdarzenie</h2>
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
        <button type="button" class="btn danger" id="eventDelete" style="display:none;">Usun</button>
        <button type="button" class="btn secondary" id="eventUnapprove" style="display:none;">Cofnij zatwierdzenie</button>
        <button type="button" class="btn" id="eventApprove" style="display:none;">Zatwierdz</button>
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
  <p id="popStatusWrap" style="display:none;"><strong>Status:</strong> <span id="popStatus"></span></p>
  <p id="popNoteWrap" style="display:none;"><strong>Notatka:</strong> <span id="popNote"></span></p>
  <div class="popover-actions">
    <button type="button" class="btn secondary" id="popClose">Zamknij</button>
    <button type="button" class="btn" id="popApprove" style="display:none;">Zatwierdz</button>
    <button type="button" class="btn secondary" id="popUnapprove" style="display:none;">Cofnij zatwierdzenie</button>
    <button type="button" class="btn danger" id="popDelete" style="display:none;">Usun</button>
  </div>
</div>

<script>
const MONTH_NAMES = ['styczeń','luty','marzec','kwiecień','maj','czerwiec','lipiec','sierpień','wrzesień','październik','listopad','grudzień'];
const WEEKDAY_ABBR = ['niedz','pon','wt','śr','czw','pt','sob'];
const MONTH_ABBR = ['sty','lut','mar','kwi','maj','cze','lip','sie','wrz','paź','lis','gru'];

let allEvents = [];
let allCalendars = [];
let appMeta = { approvalEnabled: <?= $approvalEnabled ? 'true' : 'false' ?>, role: <?= json_encode($user['role']) ?> };
let hiddenUsers = new Set();
let hiddenTypes = new Set();
let currentYear = new Date().getFullYear();
let currentView = 'year';

const modalAdd = document.getElementById('modalAdd');
const formError = document.getElementById('formError');

// --- Modal dodawania / edycji zdarzenia (ten sam formularz dla obu trybow) ---
let editingEventId = null;

function resetEventForm() {
  document.getElementById('eventForm').reset();
  formError.style.display = 'none';
  document.getElementById('eventDelete').style.display = 'none';
  document.getElementById('eventApprove').style.display = 'none';
  document.getElementById('eventUnapprove').style.display = 'none';
}

function openCreateModal(dateStr) {
  editingEventId = null;
  resetEventForm();
  document.getElementById('eventModalTitle').textContent = 'Nowe zdarzenie';
  if (dateStr) {
    document.getElementById('startDate').value = dateStr;
    document.getElementById('endDate').value = dateStr;
  }
  modalAdd.classList.add('open');
}

function openEditModal(ev) {
  editingEventId = ev.id;
  resetEventForm();
  document.getElementById('eventModalTitle').textContent = 'Edytuj zdarzenie';
  document.getElementById('startDate').value = ev.start;
  document.getElementById('endDate').value = ev.endInclusive;
  document.getElementById('type').value = ev.extendedProps.type;
  document.getElementById('note').value = ev.extendedProps.note || '';
  document.getElementById('eventDelete').style.display = ev.extendedProps.canDelete ? 'inline-block' : 'none';
  document.getElementById('eventApprove').style.display = ev.extendedProps.canApprove ? 'inline-block' : 'none';
  document.getElementById('eventUnapprove').style.display = ev.extendedProps.canUnapprove ? 'inline-block' : 'none';
  modalAdd.classList.add('open');
}

document.getElementById('btnAdd').addEventListener('click', () => openCreateModal(null));
document.getElementById('btnCancel').addEventListener('click', () => modalAdd.classList.remove('open'));

document.getElementById('eventForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const params = new URLSearchParams({
    startDate: document.getElementById('startDate').value,
    endDate: document.getElementById('endDate').value,
    type: document.getElementById('type').value,
    note: document.getElementById('note').value,
  });
  if (editingEventId) params.set('id', editingEventId);
  const res = await fetch(editingEventId ? 'events_update.php' : 'events_create.php', {
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

document.getElementById('eventDelete').addEventListener('click', async () => {
  if (!editingEventId) return;
  if (!confirm('Usunac to zdarzenie?')) return;
  const res = await fetch('events_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(editingEventId),
  });
  if (!res.ok) {
    const body = await res.json();
    alert(body.error || 'Nie udalo sie usunac zdarzenia.');
  }
  modalAdd.classList.remove('open');
  await loadData();
  refreshCurrentView();
});

async function sendApprovalFromModal(action) {
  if (!editingEventId) return;
  const res = await fetch('events_approve.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(editingEventId) + '&action=' + action,
  });
  if (!res.ok) {
    const body = await res.json();
    alert(body.error || 'Nie udalo sie zapisac zmiany statusu.');
  }
  modalAdd.classList.remove('open');
  await loadData();
  refreshCurrentView();
}
document.getElementById('eventApprove').addEventListener('click', () => sendApprovalFromModal('approve'));
document.getElementById('eventUnapprove').addEventListener('click', () => sendApprovalFromModal('unapprove'));

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

  const statusWrap = document.getElementById('popStatusWrap');
  if (appMeta.approvalEnabled) {
    statusWrap.style.display = 'block';
    document.getElementById('popStatus').textContent = ev.extendedProps.statusLabel;
  } else {
    statusWrap.style.display = 'none';
  }

  const noteWrap = document.getElementById('popNoteWrap');
  if (ev.extendedProps.note) {
    noteWrap.style.display = 'block';
    document.getElementById('popNote').textContent = ev.extendedProps.note;
  } else {
    noteWrap.style.display = 'none';
  }
  document.getElementById('popDelete').style.display = ev.extendedProps.canDelete ? 'inline-block' : 'none';
  document.getElementById('popApprove').style.display = ev.extendedProps.canApprove ? 'inline-block' : 'none';
  document.getElementById('popUnapprove').style.display = ev.extendedProps.canUnapprove ? 'inline-block' : 'none';

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
  const res = await fetch('events_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(selectedEventId),
  });
  if (!res.ok) {
    const body = await res.json();
    alert(body.error || 'Nie udalo sie usunac zdarzenia.');
  }
  popover.classList.remove('open');
  await loadData();
  refreshCurrentView();
});

async function sendApproval(action) {
  if (!selectedEventId) return;
  const res = await fetch('events_approve.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(selectedEventId) + '&action=' + action,
  });
  if (!res.ok) {
    const body = await res.json();
    alert(body.error || 'Nie udalo sie zapisac zmiany statusu.');
  }
  popover.classList.remove('open');
  await loadData();
  refreshCurrentView();
}
document.getElementById('popApprove').addEventListener('click', () => sendApproval('approve'));
document.getElementById('popUnapprove').addEventListener('click', () => sendApproval('unapprove'));

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
  const calBody = await calRes.json();
  allCalendars = calBody.calendars || [];
  appMeta = calBody.meta || appMeta;
  const rawEvents = await evRes.json();
  allEvents = rawEvents.map(ev => ({
    ...ev,
    endInclusive: addDays(ev.end, -1),
  }));
}

function addDays(dateStr, delta) {
  // Uwaga: celowo NIE uzywamy toISOString() tutaj - ono konwertuje do UTC,
  // co w strefach na wschod od UTC (np. Polska) przesuwaloby date o 1 dzien
  // wstecz. Budujemy string z lokalnych skladowych daty.
  const d = new Date(dateStr + 'T00:00:00');
  d.setDate(d.getDate() + delta);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function dateToStr(year, month, day) {
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
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

// Stan aktywnego przeciagania (przesuwanie / zmiana rozmiaru pasków).
let dragState = null;

function startDrag(e, item, bar, track, mode, month, dCount) {
  if (e.button !== undefined && e.button !== 0) return; // tylko lewy przycisk
  e.preventDefault();
  e.stopPropagation();
  const trackRect = track.getBoundingClientRect();
  dragState = {
    mode,
    item,
    bar,
    month,
    dCount,
    startClientX: e.clientX,
    pxPerDay: trackRect.width / 31,
    origStartDay: item.startDay,
    origEndDay: item.endDay,
    pendingStart: item.startDay,
    pendingEnd: item.endDay,
    moved: false,
  };
  document.addEventListener('mousemove', onDragMove);
  document.addEventListener('mouseup', onDragEnd);
}

function onDragMove(e) {
  if (!dragState) return;
  const deltaPx = e.clientX - dragState.startClientX;
  if (Math.abs(deltaPx) > 3) dragState.moved = true;
  const dayDelta = Math.round(deltaPx / dragState.pxPerDay);

  let newStart = dragState.origStartDay;
  let newEnd = dragState.origEndDay;

  if (dragState.mode === 'move') {
    newStart = dragState.origStartDay + dayDelta;
    newEnd = dragState.origEndDay + dayDelta;
    if (newStart < 1) { newEnd += (1 - newStart); newStart = 1; }
    if (newEnd > dragState.dCount) { newStart -= (newEnd - dragState.dCount); newEnd = dragState.dCount; }
    newStart = Math.max(1, newStart);
  } else if (dragState.mode === 'resize-left') {
    newStart = Math.min(dragState.origEndDay, Math.max(1, dragState.origStartDay + dayDelta));
  } else if (dragState.mode === 'resize-right') {
    newEnd = Math.max(dragState.origStartDay, Math.min(dragState.dCount, dragState.origEndDay + dayDelta));
  }

  dragState.pendingStart = newStart;
  dragState.pendingEnd = newEnd;

  const left = ((newStart - 1) / 31) * 100;
  const width = ((newEnd - newStart + 1) / 31) * 100;
  dragState.bar.style.left = left + '%';
  dragState.bar.style.width = width + '%';
  dragState.bar.classList.add('dragging');
}

async function onDragEnd(e) {
  document.removeEventListener('mousemove', onDragMove);
  document.removeEventListener('mouseup', onDragEnd);
  if (!dragState) return;
  const state = dragState;
  dragState = null;
  state.bar.classList.remove('dragging');

  if (!state.moved) {
    openEditModal(state.item.ev);
    return;
  }

  const newStartStr = dateToStr(currentYear, state.month, state.pendingStart);
  const newEndStr = dateToStr(currentYear, state.month, state.pendingEnd);

  state.item.ev.start = newStartStr;
  state.item.ev.endInclusive = newEndStr;
  state.item.ev.end = addDays(newEndStr, 1);

  const params = new URLSearchParams({ id: state.item.ev.id, startDate: newStartStr, endDate: newEndStr });
  try {
    const res = await fetch('events_update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString(),
    });
    const body = await res.json();
    if (!res.ok) {
      alert(body.error || 'Nie udalo sie zapisac zmiany.');
      await loadData();
    }
  } catch (err) {
    alert('Blad polaczenia - zmiana nie zostala zapisana.');
    await loadData();
  }
  renderYear();
}

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
        cell.classList.add('clickable');
        cell.title = 'Dodaj zdarzenie';
        const cellDateStr = dateToStr(currentYear, m, d);
        cell.addEventListener('click', () => openCreateModal(cellDateStr));
      }
      cells.appendChild(cell);
    }
    track.appendChild(cells);

    const monthStart = new Date(currentYear, m - 1, 1);
    const monthEnd = new Date(currentYear, m - 1, dCount);
    const monthStartStr = dateToStr(currentYear, m, 1);
    const monthEndStr = dateToStr(currentYear, m, dCount);

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

      if (appMeta.approvalEnabled && item.ev.extendedProps.status === 'wprowadzony') {
        bar.classList.add('status-pending');
      }

      const clippedStart = item.ev.start < monthStartStr;
      const clippedEnd = item.ev.endInclusive > monthEndStr;
      const draggable = item.ev.extendedProps.canEdit && !clippedStart && !clippedEnd;

      if (draggable) {
        bar.classList.add('draggable');
        bar.title = 'Przeciagnij, aby przesunac; ciagnij za brzeg, aby zmienic liczbe dni.';

        const handleLeft = document.createElement('div');
        handleLeft.className = 'resize-handle resize-handle-left';
        handleLeft.addEventListener('mousedown', (e) => startDrag(e, item, bar, track, 'resize-left', m, dCount));

        const handleRight = document.createElement('div');
        handleRight.className = 'resize-handle resize-handle-right';
        handleRight.addEventListener('mousedown', (e) => startDrag(e, item, bar, track, 'resize-right', m, dCount));

        bar.append(handleLeft, handleRight);
        bar.addEventListener('mousedown', (e) => {
          if (e.target.classList.contains('resize-handle')) return;
          startDrag(e, item, bar, track, 'move', m, dCount);
        });
      } else if (item.ev.extendedProps.canEdit) {
        // Edytowalne, ale zdarzenie wychodzi poza ten miesiac - bez
        // przeciagania pikselowego (nie da sie jednoznacznie policzyc dni
        // poza widocznym wycinkiem), ale klikniecie wciaz otwiera edycje.
        bar.addEventListener('click', (e) => {
          e.stopPropagation();
          openEditModal(item.ev);
        });
      } else {
        bar.addEventListener('click', (e) => {
          e.stopPropagation();
          showPopover(item.ev, bar.getBoundingClientRect());
        });
      }

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
    eventClick: (info) => {
      const data = popoverDataFromFcEvent(info.event);
      if (data.extendedProps.canEdit) openEditModal(data);
      else showPopover(data, info.el.getBoundingClientRect());
    },
    dateClick: (info) => openCreateModal(info.dateStr),
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
    eventClick: (info) => {
      const data = popoverDataFromFcEvent(info.event);
      if (data.extendedProps.canEdit) openEditModal(data);
      else showPopover(data, info.el.getBoundingClientRect());
    },
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

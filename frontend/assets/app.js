// Shared behaviour for the CRM pages. Each part only runs on pages that have the matching elements.

// ---------- Tabs (detail and edit views) ----------
document.querySelectorAll('[data-tabs]').forEach(function (group) {
  group.addEventListener('click', function (event) {
    var tabButton = event.target.closest('[data-tab]');
    if (!tabButton) return;
    var target = tabButton.dataset.tab;
    group.querySelectorAll('[data-tab]').forEach(function (tab) {
      tab.setAttribute('aria-selected', tab === tabButton ? 'true' : 'false');
    });
    group.querySelectorAll('[data-tabpanel]').forEach(function (panel) {
      panel.hidden = panel.dataset.tabpanel !== target;
    });
  });
});

// ---------- Confirm before submitting (list view delete) ----------
document.addEventListener('submit', function (event) {
  var message = event.target.dataset.confirm;
  if (message && !confirm(message)) event.preventDefault();
});

// ---------- Add Customer modal (list view) ----------
document.querySelectorAll('[data-open-modal]').forEach(function (button) {
  button.addEventListener('click', function () {
    document.getElementById(button.dataset.openModal).classList.add('show');
  });
});
document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
  backdrop.addEventListener('click', function (event) {
    if (event.target === backdrop || event.target.closest('[data-close-modal]')) {
      backdrop.classList.remove('show');
    }
  });
});

// ---------- Select all rows (list view) ----------
var selectAll = document.querySelector('[data-select-all]');
if (selectAll) {
  selectAll.addEventListener('change', function () {
    document.querySelectorAll('[data-row-select]').forEach(function (box) {
      box.checked = selectAll.checked;
    });
  });
}

// ---------- Actions menu (detail view) ----------
document.querySelectorAll('[data-action]').forEach(function (item) {
  item.addEventListener('click', function () {
    item.blur(); // closes the menu
    if (item.dataset.action === 'delete') {
      if (confirm('Delete this customer?')) {
        // Later: fetch('/api/contacts/ID', { method: 'DELETE', credentials: 'include' })
        alert('Delete is not connected to the API yet.');
      }
    } else {
      alert(item.textContent + ' is not connected to the API yet.');
    }
  });
});

// ---------- Notes (detail view, not saved yet) ----------
var notes = [];
function renderNotes() {
  document.querySelectorAll('.note-list').forEach(function (list) {
    list.replaceChildren();
    notes.forEach(function (n) {
      var li = document.createElement('li');
      var time = document.createElement('time');
      var p = document.createElement('p');
      time.textContent = n.time;
      p.textContent = n.text;
      li.append(time, p);
      list.append(li);
    });
  });
}
document.querySelectorAll('.note-form').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var input = form.querySelector('input');
    var text = input.value.trim();
    if (!text) return;
    notes.unshift({ text: text, time: new Date().toLocaleString() });
    input.value = '';
    renderNotes();
  });
});

// ---------- Tags (edit view): press Enter to add, click x to remove ----------
var tagBox = document.getElementById('tags');
var tagInput = document.getElementById('tag-input');

if (tagBox && tagInput) {
  var addTag = function (text) {
    var count = tagBox.querySelectorAll('.tag').length;
    var chip = document.createElement('span');
    chip.className = 'tag' + (count % 2 ? ' tag--gold' : '');
    chip.append(text + ' ');
    var x = document.createElement('button');
    x.type = 'button'; x.className = 'tag-x';
    x.setAttribute('aria-label', 'Remove ' + text);
    x.innerHTML = '&times;';
    var hidden = document.createElement('input');
    hidden.type = 'hidden'; hidden.name = 'tags[]'; hidden.value = text;
    chip.append(x, hidden);
    tagBox.insertBefore(chip, tagInput);
  };

  tagInput.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault(); // Enter should add a tag, not submit the form
    var text = tagInput.value.trim();
    if (text) addTag(text);
    tagInput.value = '';
  });

  tagBox.addEventListener('click', function (e) {
    var x = e.target.closest('.tag-x');
    if (x) { x.parentElement.remove(); tagInput.focus(); }
  });
}

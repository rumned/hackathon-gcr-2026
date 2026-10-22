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

// ---------- Confirm before submitting (delete) ----------
document.addEventListener('submit', function (event) {
  var message = event.target.dataset.confirm;
  if (message && !confirm(message)) event.preventDefault();
});

// ---------- Modals (Add Customer, Apply discount) ----------
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

// ---------- Task checkboxes (detail view): ticking one saves it ----------
document.querySelectorAll('[data-autosubmit]').forEach(function (box) {
  box.addEventListener('change', function () {
    box.form.submit();
  });
});

// ---------- Customer Type (edit view): show only the fields that type uses ----------
// Rules come from includes/customer-types.php. Hidden groups are disabled, so they are not sent.
var typeForm = document.querySelector('[data-type-rules]');
if (typeForm) {
  var typeRules = JSON.parse(typeForm.dataset.typeRules);
  var typeSelect = typeForm.querySelector('[data-type-select]');
  typeSelect.addEventListener('change', function () {
    var groups = typeRules.types[typeSelect.value];   // undefined: no type, show everything
    typeForm.querySelectorAll('[data-group]').forEach(function (group) {
      var name = group.dataset.group;
      var show = !groups || typeRules.common.indexOf(name) !== -1 || groups.indexOf(name) !== -1;
      group.hidden = !show;
      group.disabled = !show;
    });
  });
}

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

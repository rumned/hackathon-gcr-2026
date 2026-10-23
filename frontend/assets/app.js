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

// ---------- Promotion / discount for a customer with no quotation (detail view) ----------
// Shows the deal or the item rows, whichever is chosen. The hidden one is disabled, so it is not sent.
var quoteForm = document.querySelector('[data-quote-form]');
if (quoteForm) {
  quoteForm.querySelectorAll('[data-quote-source]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      quoteForm.querySelectorAll('[data-quote-panel]').forEach(function (panel) {
        var show = panel.dataset.quotePanel === radio.value;
        panel.hidden = !show;
        panel.disabled = !show;
      });
    });
  });

  // Add item: copies the last row with its fields emptied (quantity back to 1)
  quoteForm.querySelector('[data-add-item]').addEventListener('click', function () {
    var rows = quoteForm.querySelector('[data-quote-items]');
    var row = rows.lastElementChild.cloneNode(true);
    row.querySelectorAll('input').forEach(function (input) {
      input.value = input.name === 'item_quantity[]' ? '1' : '';
    });
    rows.appendChild(row);
    row.querySelector('input').focus();
  });
}

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

// ---------- Customer Type and Stage (edit view, Add Customer): show only the fields they use ----------
// Rules come from includes/customer-types.php. Hidden groups are disabled, so they are not sent.
document.querySelectorAll('[data-type-rules]').forEach(function (typeForm) {
  var typeRules = JSON.parse(typeForm.dataset.typeRules);
  var kindSelect = typeForm.querySelector('[data-kind-select]');
  var stageSelect = typeForm.querySelector('[data-stage-select]');

  // Same check as type_shows() in customer-types.php
  var allows = function (list, known, value) {
    return !list || known.indexOf(value) === -1 || list.indexOf(value) !== -1;
  };

  var apply = function () {
    var kind = kindSelect ? kindSelect.value : '';
    var stage = stageSelect ? stageSelect.value : '';
    typeForm.querySelectorAll('[data-group]').forEach(function (group) {
      var rule = typeRules.rules[group.dataset.group] || {};
      var show = allows(rule.kinds, typeRules.kinds, kind) && allows(rule.stages, typeRules.stages, stage);
      group.hidden = !show;
      group.disabled = !show;
    });
  };

  [kindSelect, stageSelect].forEach(function (select) {
    if (select) select.addEventListener('change', apply);
  });
});

// ---------- Stages board: drag a card to another column to change the customer's stage ----------
var board = document.querySelector('[data-board]');
if (board) {
  var stageForm = function (card, status) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="move">'
      + '<input type="hidden" name="id">'
      + '<input type="hidden" name="status">';
    form.querySelector('[name="id"]').value = card.dataset.id;
    form.querySelector('[name="status"]').value = status;
    document.body.appendChild(form);
    form.submit();
  };

  var dragged = null;
  board.addEventListener('dragstart', function (event) {
    dragged = event.target.closest('.board-card');
    if (!dragged) return;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', dragged.dataset.id);
    dragged.classList.add('is-dragging');
  });
  board.addEventListener('dragend', function () {
    if (dragged) dragged.classList.remove('is-dragging');
    dragged = null;
    board.querySelectorAll('.is-over').forEach(function (zone) { zone.classList.remove('is-over'); });
  });
  board.addEventListener('dragover', function (event) {
    var zone = event.target.closest('[data-dropzone]');
    if (!zone || !dragged) return;
    event.preventDefault();   // allows the drop
    board.querySelectorAll('.is-over').forEach(function (z) { if (z !== zone) z.classList.remove('is-over'); });
    zone.classList.add('is-over');
  });
  board.addEventListener('drop', function (event) {
    var zone = event.target.closest('[data-dropzone]');
    if (!zone || !dragged) return;
    event.preventDefault();
    var status = zone.closest('[data-status]').dataset.status;
    if (dragged.parentElement === zone) return;
    stageForm(dragged, status);
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

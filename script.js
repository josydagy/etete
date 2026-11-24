'use strict';
// Small progressive enhancement shared by pages that include this script.
document.addEventListener('click', function (event) {
  const trigger = event.target.closest('[data-confirm]');
  if (trigger && !window.confirm(trigger.getAttribute('data-confirm') || 'Are you sure?')) {
    event.preventDefault();
  }
});

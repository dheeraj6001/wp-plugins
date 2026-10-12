/*
 * The script of the TrigrExam plugin for the site. WordPress loads it on a page that shows an enquiry form.
 * It opens and closes the "Quick Enquiry" popup and sends the enquiry forms without reloading the page.
 * Without JavaScript the forms post normally.
 *
 * trigrexamEnquiry comes from the plugin: url is the address the forms are posted to and failure the message
 * when the server cannot be reached.
 */
(function () {
  var settings = window.trigrexamEnquiry || {};

  // The "Quick Enquiry" popup: the button opens the popup next to it, the cross closes it.

  function popupOf(button) {
    var holder = button.closest('.openenquiry');
    var next = holder ? holder.nextElementSibling : null;
    return next && next.classList.contains('form-popup') ? next : document.querySelector('.form-popup');
  }

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target.closest) {
      return;
    }
    var open = target.closest('.openenquiry .open-button');
    var close = target.closest('.form-popup .close');
    if (open) {
      var popup = popupOf(open);
      if (popup) {
        popup.style.display = 'block';
      }
    } else if (close) {
      close.closest('.form-popup').style.display = 'none';
    }
  });

  // The enquiry forms: the errors and the message of the server are shown in the form.

  function clear(form) {
    var old = form.querySelectorAll('.wpcf7-not-valid-tip, .wpcf7-response-output');
    for (var i = 0; i < old.length; i++) {
      old[i].parentNode.removeChild(old[i]);
    }
  }

  function show(form, result) {
    var errors = result.errors || {};
    for (var field in errors) {
      var input = form.querySelector('[name="enquiry_' + field + '"]');
      if (!input) {
        continue;
      }
      var tip = document.createElement('span');
      tip.className = 'wpcf7-not-valid-tip';
      tip.textContent = errors[field];
      input.closest('p').appendChild(tip);
    }
    if (result.message) {
      var box = document.createElement('div');
      box.className = 'wpcf7-response-output ' + (result.sent ? 'wpcf7-mail-sent-ok' : 'wpcf7-validation-errors');
      box.textContent = result.message;
      form.appendChild(box);
    }
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.querySelector || !form.querySelector('input[name="trigrexam_enquiry"]')) {
      return;
    }
    if (!settings.url || !window.fetch || !window.FormData) {
      return;
    }
    event.preventDefault();

    var button = form.querySelector('input[type="submit"]');
    var data = new FormData(form);
    data.append('action', 'trigrexam_enquiry');
    button.disabled = true;
    clear(form);

    fetch(settings.url, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        show(form, result);
        if (result.sent) {
          form.reset();
        }
      })
      .catch(function () {
        show(form, { sent: false, message: settings.failure || '' });
      })
      .then(function () {
        button.disabled = false;
      });
  });
})();

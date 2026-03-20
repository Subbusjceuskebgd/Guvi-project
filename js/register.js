const SVG_EYE_ON  = '<svg class="eye-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
const SVG_EYE_OFF = '<svg class="eye-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

$(document).ready(function () {

  /* ── Password toggle with SVG ── */
  $(document).on('click', '.toggle-pw', function () {
    const target = $('#' + $(this).data('target'));
    const type = target.attr('type') === 'password' ? 'text' : 'password';
    target.attr('type', type);
    $(this).html(type === 'password' ? SVG_EYE_ON : SVG_EYE_OFF);
  });

  /* ── Helpers ── */
  function showError(fieldId, errId) {
    $('#' + fieldId).addClass('is-invalid');
    $('#' + errId).show();
  }

  function clearError(fieldId, errId) {
    $('#' + fieldId).removeClass('is-invalid');
    $('#' + errId).hide();
  }

  function setLoading(loading) {
    if (loading) {
      $('#btn_register').prop('disabled', true)
        .html('<span class="spinner"></span>Creating Account…');
    } else {
      $('#btn_register').prop('disabled', false)
        .html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg> Create Account');
    }
  }

  function showAlert(type, msg) {
    const svgCheck  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:middle;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
    const svgAlert  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    $('.alert-custom').hide();
    if (type === 'success') {
      $('#alert_success').html(svgCheck + (msg || 'Account created! Redirecting to login…')).fadeIn(300);
    } else {
      $('#alert_error').html(svgAlert + (msg || 'Something went wrong.')).fadeIn(300);
    }
  }

  /* ── Validate ── */
  function validate() {
    let valid = true;
    const name  = $('#reg_name').val().trim();
    const email = $('#reg_email').val().trim();
    const uname = $('#reg_username').val().trim();
    const pass  = $('#reg_password').val();
    const conf  = $('#reg_confirm').val();

    clearError('reg_name',     'err_name');
    clearError('reg_email',    'err_email');
    clearError('reg_username', 'err_username');
    clearError('reg_password', 'err_password');
    clearError('reg_confirm',  'err_confirm');

    if (name.length < 2)                          { showError('reg_name',     'err_name');     valid = false; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ showError('reg_email',    'err_email');    valid = false; }
    if (!/^[a-zA-Z0-9_]{3,20}$/.test(uname))      { showError('reg_username', 'err_username'); valid = false; }
    if (pass.length < 8)                           { showError('reg_password', 'err_password'); valid = false; }
    if (pass !== conf)                             { showError('reg_confirm',  'err_confirm');  valid = false; }

    return valid;
  }

  /* ── Register click ── */
  $('#btn_register').on('click', function () {
    if (!validate()) return;
    setLoading(true);
    $('.alert-custom').hide();

    $.ajax({
      url: 'php/register.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        name:     $('#reg_name').val().trim(),
        email:    $('#reg_email').val().trim(),
        username: $('#reg_username').val().trim(),
        password: $('#reg_password').val()
      }),
      dataType: 'json',
      success: function (res) {
        setLoading(false);
        if (res.success) {
          showAlert('success', 'Account created! Redirecting to login…');
          setTimeout(function () { window.location.href = 'login.html'; }, 1800);
        } else {
          showAlert('error', res.message || 'Registration failed.');
        }
      },
      error: function (xhr) {
        setLoading(false);
        let msg = 'Server error. Please try again.';
        try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
        showAlert('error', msg);
      }
    });
  });

  $(document).on('keydown', 'input', function (e) {
    if (e.key === 'Enter') $('#btn_register').trigger('click');
  });
});
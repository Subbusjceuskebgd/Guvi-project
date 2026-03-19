var SVG_EYE_ON  = '<svg class="eye-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
var SVG_EYE_OFF = '<svg class="eye-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

$(document).ready(function () {

  if (localStorage.getItem('guvi_token')) {
    window.location.href = 'profile.html';
    return;
  }

  /* ── Password toggle with SVG ── */
  $(document).on('click', '.toggle-pw', function () {
    var target = $('#' + $(this).data('target'));
    var type = target.attr('type') === 'password' ? 'text' : 'password';
    target.attr('type', type);
    $(this).html(type === 'password' ? SVG_EYE_ON : SVG_EYE_OFF);
  });

  /* ── Helpers ── */
  function showError(fieldId, errId) {
    $('#' + fieldId).addClass('is-invalid');
    $('#' + errId).show();
  }

  function clearErrors() {
    $('#login_identifier, #login_password').removeClass('is-invalid');
    $('#err_identifier, #err_login_password').hide();
  }

  function setLoading(loading) {
    if (loading) {
      $('#btn_login').prop('disabled', true)
        .html('<span class="spinner"></span>Signing In…');
    } else {
      $('#btn_login').prop('disabled', false)
        .html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Sign In');
    }
  }

  function showAlert(type, msg) {
    var svgCheck = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:middle;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
    var svgAlert = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    $('.alert-custom').hide();
    if (type === 'success') {
      $('#alert_success').html(svgCheck + (msg || 'Login successful! Redirecting…')).fadeIn(300);
    } else {
      $('#alert_error').html(svgAlert + (msg || 'Invalid credentials.')).fadeIn(300);
    }
  }

  function validate() {
    clearErrors();
    var valid = true;
    if (!$('#login_identifier').val().trim()) { showError('login_identifier', 'err_identifier');       valid = false; }
    if (!$('#login_password').val())           { showError('login_password',   'err_login_password');  valid = false; }
    return valid;
  }

  /* ── Login click ── */
  $('#btn_login').on('click', function () {
    if (!validate()) return;
    setLoading(true);
    $('.alert-custom').hide();

    $.ajax({
      url: 'php/login.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        identifier: $('#login_identifier').val().trim(),
        password:   $('#login_password').val()
      }),
      dataType: 'json',
      success: function (res) {
        setLoading(false);
        if (res.success) {
          localStorage.setItem('guvi_token',    res.token);
          localStorage.setItem('guvi_user_id',  res.user_id);
          localStorage.setItem('guvi_username', res.username);
          localStorage.setItem('guvi_name',     res.name);
          localStorage.setItem('guvi_email',    res.email);
          showAlert('success', 'Login successful! Redirecting…');
          setTimeout(function () { window.location.href = 'profile.html'; }, 1400);
        } else {
          showAlert('error', res.message || 'Invalid credentials.');
        }
      },
      error: function (xhr) {
        setLoading(false);
        var msg = 'Server error. Please try again.';
        try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
        showAlert('error', msg);
      }
    });
  });

  $(document).on('keydown', 'input', function (e) {
    if (e.key === 'Enter') $('#btn_login').trigger('click');
  });
});
$(document).ready(function () {

  var token   = localStorage.getItem('guvi_token');
  const userId   = localStorage.getItem('guvi_user_id');  
  const username = localStorage.getItem('guvi_username'); 
  const name     = localStorage.getItem('guvi_name');       
  const email    = localStorage.getItem('guvi_email');

  /* Redirect to login if no session */
  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  /* ── Populate static account info ── */
  var initials = name.split(' ').map(function (w) { return w[0]; }).join('').toUpperCase().slice(0, 2) || 'U';
  $('#avatar_initials').text(initials);
  $('#display_name').text(name);
  $('#display_email').text(email);
  $('#info_name').val(name);
  $('#info_email').val(email);
  $('#info_username').val(username);

  /* ── Helpers ── */
  function showAlert(type, msg) {
    $('.alert-custom').hide();
    var el = type === 'success' ? '#alert_success' : '#alert_error';
    if (msg) $(el).text(msg);
    $(el).fadeIn(300);
    setTimeout(function () { $(el).fadeOut(400); }, 3500);
  }

  function setLoading(loading) {
    if (loading) {
      $('#btn_save').prop('disabled', true)
        .html('<span class="spinner"></span>Saving…');
    } else {
      $('#btn_save').prop('disabled', false).text('Save Profile');
    }
  }

  /* ── Load existing profile from MongoDB ── */
  $.ajax({
    url: 'php/profile.php',
    method: 'GET',
    data: { action: 'get', token: token, user_id: userId },
    dataType: 'json',
    success: function (res) {
      if (res.success && res.profile) {
        var p = res.profile;
        $('#prof_age').val(p.age || '');
        $('#prof_dob').val(p.dob || '');
        $('#prof_contact').val(p.contact || '');
        $('#prof_gender').val(p.gender || '');
        $('#prof_city').val(p.city || '');
        $('#prof_qualification').val(p.qualification || '');
        $('#prof_bio').val(p.bio || '');
      } else if (!res.success && res.redirect) {
        /* Token invalid / expired */
        localStorage.clear();
        window.location.href = 'login.html';
      }
    },
    error: function () {
      showAlert('error', 'Could not load profile data.');
    }
  });

  /* ── Save profile ── */
  $('#btn_save').on('click', function () {
    setLoading(true);
    $('.alert-custom').hide();

    var profileData = {
      action:        'update',
      token:         token,
      user_id:       userId,
      age:           $('#prof_age').val().trim(),
      dob:           $('#prof_dob').val(),
      contact:       $('#prof_contact').val().trim(),
      gender:        $('#prof_gender').val(),
      city:          $('#prof_city').val().trim(),
      qualification: $('#prof_qualification').val(),
      bio:           $('#prof_bio').val().trim()
    };

    $.ajax({
      url: 'php/profile.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(profileData),
      dataType: 'json',
      success: function (res) {
        setLoading(false);
        if (res.success) {
          showAlert('success', '✅ Profile updated successfully!');
        } else if (res.redirect) {
          localStorage.clear();
          window.location.href = 'login.html';
        } else {
          showAlert('error', res.message || 'Failed to save profile.');
        }
      },
      error: function () {
        setLoading(false);
        showAlert('error', 'Server error. Please try again.');
      }
    });
  });

  /* ── Logout ── */
  $('#btn_logout').on('click', function () {
    $.ajax({
      url: 'php/login.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ action: 'logout', token: token }),
      dataType: 'json',
      complete: function () {
        localStorage.clear();
        window.location.href = 'login.html';
      }
    });
  });
});
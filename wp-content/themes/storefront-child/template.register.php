<?php
/**
 * Template Name: Custom Registration Page
 */
get_header();
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light w-100 py-4">
  <div class="card shadow-sm border-0 rounded-4 p-4" style="max-width: 500px; width:100%;">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Create your account</h2>
      <p class="text-muted">Sign up to get started</p>
    </div>

    <form id="custom-register-form" class="w-100">
      <div class="mb-3 w-50 float-start pe-2">
        <label class="form-label fw-medium">First Name</label>
        <input type="text" name="first_name" class="form-control bg-light" required>
      </div>

      <div class="mb-3 w-50 float-end ps-2">
        <label class="form-label fw-medium">Last Name</label>
        <input type="text" name="last_name" class="form-control bg-light">
      </div>

      <div class="mb-3">
        <label class="form-label fw-medium">Email</label>
        <input type="email" name="email" class="form-control bg-light" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-medium">Password</label>
        <input type="password" name="password" class="form-control bg-light" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-medium">Gender</label>
        <div class="btn-group w-100" role="group">
          <input type="radio" class="btn-check" name="gender" id="male" value="Male" required>
          <label class="btn btn-outline-primary flex-fill" for="male">♂ Male</label>

          <input type="radio" class="btn-check" name="gender" id="female" value="Female">
          <label class="btn btn-outline-primary flex-fill" for="female">♀ Female</label>
        </div>
        <input type="hidden" name="gender" id="gender" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-medium">Date of Birth</label>
        <input type="date" name="dob" class="form-control bg-light" required>
      </div>

      <button type="submit" class="btn btn-green w-100 rounded-pill py-2 fw-semibold">
        Sign Up
      </button>

      <div style="display: none;" id="register-response" class="mt-3"></div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("custom-register-form");
  const responseBox = document.getElementById("register-response");

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    responseBox.innerHTML = "Processing...";

    const formData = new FormData(form);
    const dataObj = Object.fromEntries(formData.entries());

    try {
      const res = await fetch("<?php echo home_url('/wp-json/store/v1/register'); ?>", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(dataObj),
      });

      const data = await res.json();

      if (data.success) {
        responseBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        console.log("Prediction response:", data.prediction);
        setTimeout(() => window.location.href = "<?php echo home_url('/'); ?>", 1500);
      } else {
        responseBox.innerHTML = `<div class="alert alert-danger">${data.error || 'Registration failed'}</div>`;
      }
    } catch (err) {
      responseBox.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
  });
});
</script>

<?php get_footer(); ?>

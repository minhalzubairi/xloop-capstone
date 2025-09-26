<?php
/**
 * Template Name: Custom Login Page
 */

get_header();

// Handle login form submit
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log'], $_POST['pwd'])) {
    $creds = array(
        'user_login'    => sanitize_text_field($_POST['log']),
        'user_password' => $_POST['pwd'],
        'remember'      => true,
    );

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        $error = $user->get_error_message();
    } else {
        wp_redirect(home_url('/')); 
        exit;
    }
}
?>

<div class="min-vh-100 d-flex align-items-start pt-12 justify-content-center bg-light">
  <div class="card shadow-sm border-0 rounded-4" style="max-width: 420px; width:100%;">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <h2 class="fw-bold">Welcome to FreshMart</h2>
        <p class="text-muted">Sign in to your account</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>

     <form method="post" action="<?php echo wp_login_url(); ?>">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="log" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="pwd" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-green w-100">Sign In</button>
    </form>
      <div class="mt-4 text-center">
        <a href="<?php echo wp_lostpassword_url(); ?>" class="text-decoration-none fw-medium">
          Forgot password?
        </a>
        <div>
          <a href="/xloop-capstone/register" class="text-decoration-none fw-medium">
            Don’t have an account? Sign Up
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php get_footer(); ?>

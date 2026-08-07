<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Login Modal
|--------------------------------------------------------------------------
|
| Used by:
| - navbar.php
|
|--------------------------------------------------------------------------
*/

?>


<div 
class="modal-overlay" 
id="loginModal"
>


<div class="modal-box login-modal">



<button 
class="modal-close"
onclick="closeLoginModal()"
>

&times;

</button>




<div class="modal-header">


<img 
src="<?= IMAGE_URL; ?>logo.jpg"
alt="HochipoHub"
class="modal-logo"
>


<h2>

Welcome Back

</h2>


<p>

Login to continue shopping

</p>


</div>






<form 
action="<?= BASE_URL; ?>auth/login_process.php"
method="POST"
class="auth-form"
>




<div class="form-group">


<label>

Email

</label>


<input

type="email"

name="email"

placeholder="Enter your email"

required

>


</div>






<div class="form-group">


<label>

Password

</label>


<input

type="password"

name="password"

placeholder="Enter your password"

required

>


</div>







<div class="form-options">


<label class="remember">


<input

type="checkbox"

name="remember"

>


Remember me


</label>





<a href="<?= BASE_URL; ?>auth/forgot_password.php">


Forgot Password?


</a>


</div>








<button 
type="submit"
class="auth-btn"
>


Login


</button>





</form>







<div class="modal-footer">


<p>

Don't have an account?


<button

type="button"

class="switch-btn"

onclick="switchRegister()"

>


Register


</button>


</p>


</div>





</div>


</div>
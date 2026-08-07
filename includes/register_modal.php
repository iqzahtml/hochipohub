<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Register Modal
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
id="registerModal"
>


<div class="modal-box register-modal">





<button 
class="modal-close"
onclick="closeRegisterModal()"
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

Create Account

</h2>



<p>

Join HochipoHub today

</p>



</div>







<form

action="<?= BASE_URL; ?>auth/register_process.php"

method="POST"

class="auth-form"

>







<div class="form-group">


<label>

Full Name

</label>


<input

type="text"

name="name"

placeholder="Enter your name"

required

>


</div>







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

Phone Number

</label>


<input

type="text"

name="phone"

placeholder="01X-XXXXXXX"

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

placeholder="Create password"

required

>


</div>







<div class="form-group">


<label>

Confirm Password

</label>


<input

type="password"

name="confirm_password"

placeholder="Confirm password"

required

>


</div>








<button

type="submit"

class="auth-btn"

>


Create Account


</button>







</form>








<div class="modal-footer">


<p>


Already have an account?



<button

type="button"

class="switch-btn"

onclick="switchLogin()"

>


Login


</button>



</p>


</div>






</div>


</div>
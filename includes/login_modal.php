<div 
id="loginModal"
class="modal"
>



<div class="modal-content">



<span 
class="close-modal"
onclick="closeLoginModal()"
>

&times;

</span>



<h2>

Login

</h2>




<form action="<?= BASE_URL; ?>auth/login_process.php" method="POST">



<div class="form-group">


<label>

Email

</label>


<input

type="email"

name="email"

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

required

>


</div>






<button 
type="submit"
class="btn-primary"
>

Login

</button>




</form>




<p>


Don't have account?


<button 
onclick="openRegisterModal()"
class="link-button"
>

Register

</button>


</p>




<a href="<?= BASE_URL; ?>auth/forgot_password.php">

Forgot Password?

</a>



</div>



</div>
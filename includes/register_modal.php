<div 
id="registerModal"
class="modal"
>



<div class="modal-content">



<span 
class="close-modal"
onclick="closeRegisterModal()"
>

&times;

</span>




<h2>

Create Account

</h2>





<form 

action="<?= BASE_URL; ?>auth/register_process.php"

method="POST"

>





<div class="form-group">


<label>

Full Name

</label>


<input

type="text"

name="name"

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

required

>


</div>







<div class="form-group">


<label>

Phone

</label>


<input

type="text"

name="phone"

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







<div class="form-group">


<label>

Register As

</label>



<select name="role">


<option value="customer">

Customer

</option>


<option value="vendor">

Vendor

</option>


</select>



</div>








<button

type="submit"

class="btn-primary"

>

Register

</button>







</form>







<p>


Already have account?


<button

onclick="openLoginModal()"

class="link-button"

>

Login

</button>



</p>




</div>


</div>
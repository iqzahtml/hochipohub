<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Contact Page
|--------------------------------------------------------------------------
|
| Static contact information page
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Contact";



?>



<?php include "includes/header.php"; ?>







<section class="contact-page">






<div class="page-title">


<h1>

Contact HochipoHub

</h1>



<p>

Need help? Contact our team.

</p>


</div>









<div class="contact-container">








<div class="contact-info">





<h2>

Get In Touch

</h2>





<div class="contact-item">


<i class="fa-solid fa-envelope"></i>


<div>


<h4>

Email

</h4>


<p>

support@hochipohub.com

</p>


</div>



</div>








<div class="contact-item">


<i class="fa-solid fa-phone"></i>


<div>


<h4>

Phone

</h4>


<p>

+60 1X-XXXX XXXX

</p>


</div>



</div>








<div class="contact-item">


<i class="fa-solid fa-location-dot"></i>


<div>


<h4>

Location

</h4>


<p>

Malaysia

</p>


</div>



</div>






</div>












<div class="contact-form-box">





<h2>

Send Message

</h2>






<form method="POST">






<div class="form-group">


<label>

Name

</label>


<input

type="text"

name="name"

placeholder="Your name"

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

placeholder="Your email"

required

>


</div>







<div class="form-group">


<label>

Message

</label>


<textarea

name="message"

rows="6"

placeholder="Write your message..."

required

></textarea>



</div>







<button

type="submit"

class="btn-primary"

>


Send Message


</button>







</form>







</div>








</div>








</section>








<?php include "includes/footer.php"; ?>
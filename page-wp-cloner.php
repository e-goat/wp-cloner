<?php
function create_clone_form()
{
  add_menu_page(
    'Create New Portal',
    'Create Portal',
    'remove_users',
    'create_portal',
    'create_portal_page',
    'dashicons-hammer',
    null
  );
}
add_action('admin_menu', 'create_clone_form');

function create_portal_page()
{ ?>
<div id="wrap">
  <h1>Provide the specifics for the portal that you wish to create.</h1>
  <form method="post" enctype="multipart/form-data" name="create_portal_form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="create_portal_form" class="validate" novalidate="novalidate">
    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="portal_name">Portal Name</label>
          </th>
          <td>
            <input type="text" name="portal_name" id="portal_name" pattern="[A-Za-z]{3,}" class="regular-text">
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="portal_slug">Portal Slug</label>
          </th>
          <td>
            <input type="text" name="portal_slug" id="portal_slug" class="regular-text">
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="portal_alias">Portal Alias</label>
          </th>
          <td>
            <input type="email" name="portal_alias" id="portal_alias" class="regular-text" >
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="portal_target">Portal to copy</label>
          </th>
          <td>
            <select name="portal_target" id="portal_target">
              <?php
              $allTerms = get_terms([
                'taxonomy' => 'client',
                'hide_empty' => false,
              ]);
              foreach ($allTerms as $index => $oneTerm) {
                foreach ($oneTerm as $subkey => $subvalue) {
                  if ($subkey == 'slug') {
                    echo '<option value="' . $subvalue . '">' . $subvalue . '</option>';
                  }
                }
              }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_logo_type]">Select if you like to upload your own logo or use the standard Kantar logo.</label>
          </th>
          <td>
            <select name="term_meta[class_term_logo_type]" id="logo-type">
              <option value="1">Use standard Kantar logo</option>
              <option value="2">Upload custom logo</option>
            </select>
          </td>
        </tr>
        <tr id="custom-logo">
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_meta_logo]">Portal Logo</label>
          </th>
          <td>
            <div class="file-wrapper">
              <span class="filename"></span>
              <input type="file" name="term_meta[class_term_meta_logo]" id="class_term_meta_logo" class="regular-text" accept="image/png">
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_meta_region]">Region (e.g. AMS, APAC, EMEA)</label>
          </th>
          <td>
            <div class="input-field">
              <select name="term_meta[class_term_meta_region]" id="term_meta[class_term_meta_region]">
                <option value="AMS">AMS</option>
                <option value="APAC">APAC</option>
                <option value="EMEA">EMEA</option>
              </select>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_meta_environment]">Default Environment (e.g. DEV, REAL)</label>
          </th>
          <td>
            <div class="input-field">
              <select name="term_meta[class_term_meta_environment]" id="term_meta[class_term_meta_environment]">
                <option value="DEV">DEV</option>
                <option value="REAL">REAL</option>
              </select>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_meta]">Initial Credits</label>
          </th>
          <td>
            <input type="number" name="term_meta[class_term_meta]" id="term_meta[class_term_meta]" value="999" class="regular-text">
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_meta_jn]">Initial Job Number</label>
          </th>
          <td>
            <input type="number" name="term_meta[class_term_meta_jn]" id="term_meta[class_term_meta_jn]" value="92191727" class="regular-text">
            <input type="hidden" name="action" value="create_portal_form">
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_meta_po]">Initial PO Number</label>
          </th>
          <td>
            <input type="number" name="term_meta[class_term_meta_po]" id="term_meta[class_term_meta_po]" value="" class="regular-text">
            <input type="hidden" name="action" value="create_portal_form">
          </td>
        </tr>
        <tr>
          <th scope="row" style="width:270px;">
            <label for="term_meta[class_term_meta_qarts]">Enter QARTS version</label>
          </th>
          <td>
            <input type="text" name="term_meta[class_term_meta_qarts]" id="term_meta[class_term_meta_qarts]" value="qarts.2362" class="regular-text">
            <input type="hidden" name="action" value="create_portal_form">
          </td>
        </tr>
      </tbody>
    </table>
    <p class="submit">
      <div class="lds-ellipsis">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
      </div>
      <input type="submit" name="create_portal" id="create_portal" class="button button-primary" value="Clone">
    </p>
  </form>
</div>

<script>
// ###################################//
//        # GLOBAL VARIABLES #        //
// ################################## //
const submitBtn      = document.getElementById('create_portal'),
const logoFileInput  = document.getElementsByName('portal_logo'),
const loader         = document.getElementsByClassName('lds-ellipsis'),
const slug           = document.getElementsByName('portal_slug'),
const slug_to_copy   = document.getElementsByName('portal_target'),
const copy_btn       = document.getElementsByName('create_portal')[0];

submitBtn.addEventListener('click', event => {
  event.target.style.display = 'none';
  loader[0].style.display = 'inline-block';
});

// ###################################//
//     # SLUG INPUT VALIDATIONS #     //
// ################################## //
if (slug[0].value == "") copy_btn.setAttribute('disabled', '');

slug[0].addEventListener('keyup', () => {
        // Forbid spaces in slug naming
  slug[0].value = slug[0].value.replace(/ /g, '');
  if (!matchSymbols(slug[0].value.toString())) copy_btn.setAttribute('disabled', '');
  else copy_btn.removeAttribute('disabled');
});

slug[0].addEventListener('change', event => {
  let validationText = document.createElement('p');

  if (!matchSymbols(slug[0].value.toString())) {
    if (document.querySelectorAll('.validate-slug-text').length > 0) document.querySelectorAll('.validate-slug-text')[0].remove();

    validationText.innerText = 'Incorrect slug naming.';
    validationText.style.color = "red";
    validationText.classList = "validate-slug-text";
    slug[0].after(validationText);
  } else {
    return document.querySelectorAll('.validate-slug-text')[0].remove();
  }
});

// ###################################//
//        # LOGO TYPE SELECT #        //
// ################################## //
document.getElementById("logo-type").addEventListener('change', ( event ) => {
  if (event.target.selectedIndex == 1) {
    document.querySelector("#custom-logo").style.display = "table-row";
  } else {
    document.querySelector("#custom-logo").style.display = "none";
    document.querySelector("#class_term_meta_logo").value = '';
  }
})
// ###################################//
//        # HELPER FUNCTIONS #        //
// ################################## //
const slug_regex = /^\d*[a-zA-Z]((_)*[a-zA-Z\d]){2,30}$/g,
matchSymbols     = (str) => str.match(slug_regex);
</script>

<style>
  .lds-ellipsis {
    display: none;
    position: relative;
    width: 80px;
    height: 80px;
  }

  #custom-logo {
    display: none;
  }

  .lds-ellipsis div {
    position: absolute;
    top: 33px;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: #fff;
    animation-timing-function: cubic-bezier(0, 1, 1, 0);
  }

  .lds-ellipsis div:nth-child(1) {
    left: 8px;
    animation: lds-ellipsis1 0.6s infinite;
  }

  .lds-ellipsis div:nth-child(2) {
    left: 8px;
    animation: lds-ellipsis2 0.6s infinite;
  }

  .lds-ellipsis div:nth-child(3) {
    left: 32px;
    animation: lds-ellipsis2 0.6s infinite;
  }

  .lds-ellipsis div:nth-child(4) {
    left: 56px;
    animation: lds-ellipsis3 0.6s infinite;
  }

  @keyframes lds-ellipsis1 {
    0% {
      transform: scale(0);
    }

    100% {
      transform: scale(1);
    }
  }

  @keyframes lds-ellipsis3 {
    0% {
      transform: scale(1);
    }

    100% {
      transform: scale(0);
    }
  }

  @keyframes lds-ellipsis2 {
    0% {
      transform: translate(0, 0);
    }

    100% {
      transform: translate(24px, 0);
    }
  }
</style>
<?php }
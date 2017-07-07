<script src="https://rawgit.com/oauth-io/oauth-js/master/dist/oauth.min.js"></script>
<script>
    "var PS_PAGE_ID = "..page.id..";";
    "var PS_SITE_URL = '"..site.uri.."';";
</script>

<div id="ps-box-cust" style="display:none;">
    <button id="ps-unsubscribe" class="ui-button ui-button-primary" type="button">"Unsubscribe"</button>
    <button id="ps-subscribe" class="ui-button ui-button-primary" type="button">"Subscribe"</button>
    <button id="ps-login" class="ui-button ui-button-primary" type="button">"Login"</button>
    <button id="ps-logout" class="ui-button ui-button-primary" type="button">"Logout"</button>
</div>

<div id="ps-box-int" if="user.groups['Administrators']">
    <button id="ps-notify-cust" class="ui-button ui-button-primary" type="button">"Notify Subscribers"</button>
</div>

var PS_SERVER_URL = "https://pubsub.local";

$("#ps-login").click(function() {
    ps_loginOAuth($(this).attr('page-id'), $(this).attr('site-url'));
});

$("#ps-logout").click(function() {
    ps_logoutUser($(this).attr('site-url'));
});

$("#ps-subscribe").click(function() {
    ps_subscribe($(this).attr('page-id'), $(this).attr('site-url'));
});

$("#ps-unsubscribe").click(function() {
    ps_unsubscribe($(this).attr('page-id'), $(this).attr('site-url'));
});

function ps_unsubscribe(pageId, siteURL) {
    $.ajax({
        method: "POST",
        url: PS_SERVER_URL + "/toggle",
        crossDomain: true,
        xhrFields: {
            withCredentials: true
        },
        data: {"page": pageId, "site": siteURL, "action": "unsubscribe"}
    }).done(function(data) {
        console.log(data);
        ps_loggedIn(data);
    });
}

function ps_subscribe(pageId, siteURL) {
    $.ajax({
        method: "POST",
        url: PS_SERVER_URL + "/toggle",
        crossDomain: true,
        xhrFields: {
            withCredentials: true
        },
        data: {"page": pageId, "site": siteURL, "action": "subscribe"}
    }).done(function(data) {
        console.log(data);
        ps_loggedIn(data);
    });
}

function ps_notLoggedIn() {
    $("#ps-logout").hide();
    $("#ps-subscribe").hide();
    $("#ps-unsubscribe").hide();
    $("#ps-login").show();
    $("#ps-box-cust").show();
}

function ps_loggedIn(data) {
    $("#ps-login").hide();
    $("#ps-logout").show();

    if(data.subscribed) {
        $("#ps-subscribe").hide();
        $("#ps-unsubscribe").show();
    } else {
        $("#ps-unsubscribe").hide();
        $("#ps-subscribe").show();
    }
    
    $("#ps-box-cust").show();
}

function ps_logoutUser(siteURL) {
    $.ajax({
        method: "POST",
        url: PS_SERVER_URL + "/logout",
        crossDomain: true,
        xhrFields: {
            withCredentials: true
        },
        data: {"site": siteURL}
    }).done(function(data) {
        console.log(data);
        if(!data.loggedIn) {
            ps_notLoggedIn();
        }
    });
}

function ps_loginOAuth(pageId, siteURL) {
    OAuth.initialize('token');
    OAuth.popup('facebook')
        .done(function(result) {
            ps_loginUser(result.access_token, pageId, siteURL);
        })
        .fail(function (err) {
            console.log(err);
            alert(err);
        });
}

function ps_loginUser(oauthToken, pageId, siteURL) {
    $.ajax({
        method: "POST",
        url: PS_SERVER_URL + "/login",
        crossDomain: true,
        xhrFields: {
            withCredentials: true
        },
        data: {"page": pageId, "token": oauthToken, "site": siteURL}
    }).done(function(data) {
        console.log(data);
        if(data.loggedIn) {
            ps_loggedIn(data);
        } else {
            ps_notLoggedIn();
        }
    });
 
}

function ps_initInterface(pageId, siteURL) {
    console.log("PageID: " + pageId);
    
    $("#ps-subscribe").attr('page-id', pageId);
    $("#ps-subscribe").attr('site-url', siteURL);
    
    $("#ps-unsubscribe").attr('page-id', pageId);
    $("#ps-unsubscribe").attr('site-url', siteURL);
    
    $("#ps-logout").attr('site-url', siteURL);
    $("#ps-login").attr('page-id', pageId);
    
    $("#ps-login").attr('site-url', siteURL);
    
    $.ajax({
        method: "POST",
        url: PS_SERVER_URL + "/check",
        crossDomain: true,
        xhrFields: {
            withCredentials: true
        },
        data: { "page": pageId, "site": siteURL }
    }).done(function(data) {
        console.log(data);
        if(!data.loggedIn) {
            ps_notLoggedIn();
        } else {
            ps_loggedIn(data);
        }
    });
    
}

$(function() {
    ps_initInterface(PS_PAGE_ID, PS_SITE_URL);
}); 

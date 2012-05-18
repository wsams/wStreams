// See LICENSE for licensing.

var ws = new Object();

ws.escapeHtml = function(inputString) {
    if ( typeof inputString === "undefined" ) {
        return "";
    }
    if ( inputString == "" ) {
        return "";
    }
    return inputString.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
};

ws.escapeJs = function(inputString) {
    if ( typeof inputString === "undefined" ) {
        return "";
    }
    if ( inputString == "" ) {
        return "";
    }
    return inputString.replace(/'/, "\\\\\'");
}

ws.escapeDoubleQuotes = function(inputString) {
    if ( typeof inputString === "undefined" ) {
        return "";
    }
    if ( inputString == "" ) {
        return "";
    }
    return inputString.replace(/"/, "\\\"");
}

ws.getBreadcrumbs = function (dir) {
    dir = decodeURIComponent(dir);
    var output = "";
    $.getJSON(restUrl + "?action=getBreadcrumbs&dir=" + encodeURIComponent(dir), function(json) {
        output += "<a class='getBreadcrumbs' data-dir=''>Root</a> / ";
        $.each(json, function(i, item) {
            if ( i == "breadcrumbs" ) {
                $.each(item, function(k2, crumb) {
                    output += "<a class=\"getBreadcrumbs\" data-dir=\"" + ws.escapeDoubleQuotes(crumb['url']) + "\">" + ws.escapeHtml(crumb['text']) + "</a> / ";
                });
            }
        });
        $("#breadcrumbs").html(output);
    });
};

atEnd = false;
ws.getPlayer = function (dir) {
    ws.isPlayerHidden = false;
    dir = decodeURIComponent(dir);
    var output = "";
    $.getJSON(restUrl + "?action=getPlayer&dir=" + encodeURIComponent(dir), function(json) {
        if ( json['status'] == "ok" ) {
            $("#player").html(json['player']);
            ws.getAlbumCover(dir);
            $(".jp-play-bar").watch("width", function(){
                //alert('got it. yeah.');
                var completed = $(".jp-play-bar").css("width");
                completed = completed.replace(/px$/, "");
                var total = $(".jp-seek-bar").width();
                percent = Math.round((completed / total) * 100);
                $(".jp-play-bar").html("<span id='percentleft'>" + percent + "%&#160;</span>");
            }); 
        } else {
            $("#player").html();
        }
    });
}

ws.getDirIndex = function (dir) {
    dir = decodeURIComponent(dir);
    window.location.hash = "#getDirIndex/" + dir;
    var output = "";
    $.getJSON(restUrl + "?action=getDirIndex&dir=" + encodeURIComponent(dir), function(json) {
        ws.getBreadcrumbs(dir);
        // k=key name - such as 'status', v=value of the key - such as 'ok'
        $.each(json, function(k, v) {
            if ( k == "dirIndex" ) {
                // vDir['dir'], vDir['displayDir'], vDir['url']
                // k2=integer key, vDir=Array of each directory metadata
                $.each(v, function(k2, vDir) {
                    output += "<span class='dirIndexItem getDirIndex' data-dir='" + ws.escapeJs(vDir['relDir']) + "'>" + ws.escapeHtml(vDir['displayDir']) + "</span>";
                });
            }
        });
        $("#index").html(output);
        $(".dirIndexItem").css("border-bottom", "2px solid #f0f0f0");
        $(".dirIndexItem:first").css("border-top", "2px solid #f0f0f0");
        ws.getPlayer(dir);
    });
};

ws.getHomePage = function (dir) {
    dir = decodeURIComponent(dir);
    window.location.hash = "#getHomePage";
    var output = "<div id='links'><a class='getHomePage'><img src='images/logo.png' alt='logo' /> wStreams</a> <div id='breadcrumbs'></div></div>";
    output += "<div id='player'></div>";
    output += "<div id='index'></div>";
    $("#output").html(output);
};

ws.getAlbumCover = function (dir) {
    dir = decodeURIComponent(dir);
    $.getJSON(restUrl + "?action=getAlbumCover&dir=" + encodeURIComponent(dir), function(json) {
        if ( json['status'] == "ok" ) {
            $("#jp_container_1").append("<br /><a target='_blank' href='" + ws.escapeHtml(json['coverUrl']) + "'><img class='albumCover' src='" + ws.escapeHtml(json['coverUrl']) + "' alt='Cover Art' /></a>");
        }
    });
}

ws.getPlaylist = function (dir) {
    dir = decodeURIComponent(dir);
    $.getJSON(restUrl + "?action=getPlaylist&dir=" + encodeURIComponent(dir), function(json) {
        return json;
    });
}

ws.init = function() {
    var hash = window.location.hash;
    hash = hash.replace(/^#/, "");
    ws.getHomePage();
    var hashVars = hash.split("/");
    switch(hashVars[0]) {
        case "getDirIndex":
            var dir = "";
            for ( i=1; i<hashVars.length; i++ ) {
                dir += hashVars[i] + "/";
            }
            dir = dir.replace(/\/*$/, "");
            ws.getDirIndex(dir);
            break;
        default:
            ws.getDirIndex("");
    }
}

$(document).ready(function(){
    previousDir = "";

    ws.init();

    $(".getHomePage").live("click", function(){
        ws.getHomePage();
        ws.getDirIndex("");
    });

    $(".getDirIndex").live("click", function(e){
        var dir = $(this).data('dir');
        ws.getPlaylist(dir);
        ws.getDirIndex(dir);
    });

    // Getting the breadcrumbs must also get the index
    // for the current breadcrumb. getDirIndex always calls
    // getBreadcrumbs() after it's built the index.
    $(".getBreadcrumbs").live("click", function(e){
        var dir = $(this).data('dir');
        ws.getDirIndex(dir);
        var windowHeight = $(window).height();
        var indexPosition = $("#index").first().offset().top;
        $(".jp-playlist").css("display", "none").css("visibility", "hidden");
        $(".albumCover").css("display", "none").css("visibility", "hidden");
        if ( $("a.showplaylist").size() < 1 ) {
            $(".jp-playlist").after("<a class='showplaylist'>Show playlist</a>");
        }
    });

    $(".showplaylist").live("click", function(e){
        $(".jp-playlist").css("display", "block").css("visibility", "visible");
        $(".albumCover").css("display", "block").css("visibility", "visible");
        $(".showplaylist").remove();
    });

});

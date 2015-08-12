
var cora = {
    strings: {}
};
var gui = {changeTab: function() {}};

(function() {
    var idx, len, chain;

    var initialize = function() {
        $('loginTabButton').set('active', 'true');
        $('loading').hide();
        $('main').show();
        $$('#menu ul').setStyle('visibility', 'visible');

        var uri = new URI();
        if(uri.parsed && uri.parsed.query) {
            var fid = uri.parsed.query.parseQueryString()["f"];
            var form = document.getElement('#loginDiv form');
            if(fid && form) {
                form.set('action', form.get('action') + "?f=" + fid);
            } else {
                history.replaceState({}, "", "./");
            }
        }
    };

    $LAB.setGlobalDefaults({AlwaysPreserveOrder: true});
    chain = $LAB;
    for (idx = 0, len = _srcs.framework.length; idx < len; ++idx) {
        chain = chain.script(_srcs.framework[idx]);
    }
    chain.wait(function() {
        if (document.readyState == "complete")
            initialize();
        else
            window.addEvent('domready', initialize);
    });

    // pre-load the rest anyway, but without the initializing stuff
    for (idx = 0, len = _srcs.main.length; idx < len; ++idx) {
        chain = chain.script(_srcs.main[idx]);
    }
}());
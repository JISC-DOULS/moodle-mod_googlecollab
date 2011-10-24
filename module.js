M.googlecollab = M.googlecollab || {};

M.googlecollab.viewresizer = function(Y) {
    //TODO Can probably just make this fixed
    var startingWindowHeight = Y.one('#region-main').getStyle('height');
    var newiframeheight = parseInt(startingWindowHeight) - 100;
    newiframeheight = newiframeheight > 700 ? newiframeheight : 700;
    var frame = Y.one('#googlecollab_googlewindow');
    if (frame) {
        frame.setStyle('height', newiframeheight );
    }

    resizeIFrame  = function(evt) {
        var newWindowHeight = Y.one('#region-main').getStyle('height');
        var newiframeheight = parseInt(newWindowHeight) - 100;
        newiframeheight = newiframeheight > 700 ? newiframeheight : 700;
        var frame = Y.one('#googlecollab_googlewindow');
        if (frame) {
            frame.setStyle('height', newiframeheight );
        }
    };

    //Stops loop in IE
    if (!Y.UA.ie) {
        Y.on('windowresize', resizeIFrame);
    }

    //Also trap link to gdoc select and open in new window
    Y.on("click", function(e){
        window.open(this.get('href'), '_blank');
        e.preventDefault();
    }, '#gdoclink');

};

M.googlecollab.init = function(Y, actid, errorMessage) {

    handle_reset = function(groupid) {

        if (groupid == 'all') {
             msgSelector = '.googlecollab_docs_message_all';
             disableSelector = '.googlecollab_docs_reset';
        }    else {
            msgSelector = '.googlecollab_docs_message';
            disableSelector = '#docreset_'+groupid;
        }

            url = 'ajax_docs.php';

            var cfg = {
                method : 'POST',
                data : 'actid=' + actid + '&groupid=' + groupid + '&sesskey=' + M.cfg.sesskey,
                timeout : 1000 * 60 * 1,

                on : {
                    success : function(ioId, o) {

                        try {
                             result = Y.JSON.parse(o.responseText);
                        } catch(err) {

                            Y.one(msgSelector).setContent(errorMessage);
                            return;

                        }

                        Y.one(msgSelector).setContent(result.message);
                        Y.all(disableSelector).setAttribute('disabled', true);

                    },
                    failure : function(ioId, o) {
                        Y.one(msgSelector).setContent(errorMessage);

                    }
                }
            };
            Y.io(url, cfg);

        };

    Y.all('.googlecollab_docs_reset').on('click', function(e) {
        var doc = e.target.getAttribute('id');
        var patt = /^docreset_(.+?)$/;
        var matches = doc.match(patt);
        handle_reset(matches[1]);

    });

};

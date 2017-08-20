(function($){
    var ajaxurl;

    $(document).ready(function() {
        $('.capture_plugin_button').on('click',toggle);
        ajaxurl = capture_plugin_client_settings.url;
    });

    function toggle() {
        $('.capture_plugin_button div').text("...");
        $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: { action: 'capture_plugin_toggle' },
           success: process_response
        });
    }

    function process_response(data) {
        console.log("Capture found:");
        console.log(JSON.stringify(data.new_keys));
        //if ( data.new_tables ) {
        //    var popup = $('<div>').css( {
        //        position: 'fixed',
        //        left: '20px',
        //        top: '20px',
        //        'z-index': 15000,
        //        'background-color': 'yellow',
        //        padding: '20px',
        //        'max-width': '300px'
        //    });
        //    $("<p>We got some results</p>").appendTo(popup);
        //    console.log(popup.html());
        //    $('body').append(popup);
        //}
        $('.capture_plugin_button div').text( data.label );
    }
})(jQuery);
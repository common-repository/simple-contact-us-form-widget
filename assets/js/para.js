( function( $ ) {

    $( '#contact_us_form_widget' ).submit( function( e ) {
        e.preventDefault();

        const form = $( this );
        const postData = form.serialize();

        $( '.reload_form' ).css({ 'display': 'flex' });
        $.ajax( {
            url: parajax.ajax_url,
            type: 'POST',
            data: postData + '&action=ajax_form',
            success( resp ) {
                $( form ).fadeOut( 100, function() {
                    form.html( resp ).fadeIn();
                } );
            },
            complete: function(){
                $( '.reload_form' ).hide();
            },
        } );
    } );

}( jQuery ) );

jQuery(document).ready(function($){

    $('.sn__notifier_email_details').click(function(){
        let container = $('#email-display-body');
        container.html('');
        $(this).data('emails').split(',').forEach(function(item){
            console.log(item);
            container.append(
                $('<p></p>').text(item)
            );
        });
        $('#' + $(this).data('modalId')).removeClass('modal-hide');
    });

    //register show and hide callback for all modal classes
    $('.sn__hover-modal-close').click(function(){
        $('#' + $(this).data('id')).addClass('modal-hide');
    });
});
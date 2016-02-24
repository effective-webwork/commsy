;(function(UI) {

    'use strict';

    UI.component('actionSendList', {

        defaults: {
            url: '',
            errorMessage: '',
            itemId: null
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$('[data-cs-action-send-list]', context).each(function() {
                    let element = UI.$(this);

                    if (!element.data('actionSendList')) {
                        UI.actionSendList(element, UI.Utils.options(element.attr('data-cs-action-send-list')));
                    }
                });
            });
        },

        init: function() {
            let $this = this;
            
            this.element.on('click', function() {
                event.preventDefault();

                // send ajax request
                $.ajax({
                    url: $this.options.url,
                    type: 'POST',
                    data: JSON.stringify({
                    })
                }).done(function(data, textStatus, jqXHR) {
                    if (!jqXHR.responseJSON) {
                        // if we got back html, embed the form
                        let feedDom = $('.feed');

                        if (feedDom.length) {
                            feedDom.prepend(data);
                        }

                        $this.setupForm();
                    } else {
                        console.log('json response');
                        console.log(data);
                    }

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify($this.options.errorMessage, 'danger');
                });
            });
        },

        setupForm: function() {
            let $this = this;
            
            $('.feed').find('form').submit(function (event) {
                event.preventDefault();
    
                let entries =  $('.feed').find('input:checked').map(function() {
                    return this.value;
                }).get();
    
                $('#sendList_entries').attr('value', entries);
    
                // submit the form manually
                $.ajax({
                    url: $this.options.url,
                    type: "POST",
                    data: $(this).serialize()
                })
                .done(function(result) {
                    $('.feed').find('form').remove();
                    
                    UIkit.notify({
                        message : result.message,
                        status  : result.status,
                        timeout : result.timeout,
                        pos     : 'top-center'
                    });
                });
            });
            
        }
    });

})(UIkit);
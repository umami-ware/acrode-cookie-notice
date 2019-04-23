(function($) {
    $(document).ready(function() {

        //Add new tab
        $(document).on( 'click', '.cn-add-tab button', function(){
            var index = $('.cn-tabs-content').length;
                new_index = '[tabs]['+index+']';
            var new_tab = '<div class="cn-tabs-content">'+$('.cn-tabs-content').html().replace(/\[tabs\]\[(.*?)\]/g, new_index)+'</div>';
            var new_content = $(new_tab);

            new_content.find('.cn-option-list').each(function(i){
                if( i > 0) $(this).remove();
            });
            new_content.find('textarea, input').each(function(){
                $(this).val('');
            });
            $( ".cn-tabs" ).append( new_content );

            var new_btn = '<li>New tab '+(index+1)+'<button class="cn-remove-tab"><svg><path d="M1.4 0L0 1.4 3.6 5 0 8.6 1.4 10 5 6.4 8.6 10 10 8.6 6.4 5 10 1.4 8.6 0 5 3.6 1.4 0z"/></svg></button></li>';
            $(new_btn).insertBefore( $('.cn-add-tab') );
        });

        //Remove tab
        $(document).on( 'click', '.cn-remove-tab', function(){
            $('.cn-active').removeClass('cn-active');
            $( ".cn-tabs-caption li:first" ).addClass('cn-active');
            $('.cn-tabs-content:first').addClass('cn-active');

            var index = $(".cn-tabs-caption li").index( $(this).parent() );

            if( index == $('.cn-tabs-content').length-1 ) {
                $('.cn-tabs-content:last').remove();
                $(this).parent().remove();
                return;
            }

            $( ".cn-tabs-content:eq("+index+")" ).remove();
            $(this).parent().remove();
            $('.cn-tabs-content').each(function(i){
                if( i >= index ){
                    var new_index = '[tabs]['+i+']',
                        new_tab = $(this).html().replace(/\[tabs\]\[(.*?)\]/g, new_index);
                    $(this).html(new_tab);
                }
            });
        });

        //Change tab
        $('.cn-tabs-caption').on('click', 'li:not(.cn-active,.cn-add-tab)', function() {
            $(this)
                .addClass('cn-active').siblings().removeClass('cn-active')
                .closest('.cn-tabs').find('.cn-tabs-content').removeClass('cn-active').eq($(this).index()).addClass('cn-active');
        });

        //Add new option
        $(document).on( 'click', '.cn-add-option', function(){
            var parent_option = $(this).parent().find('.cn-option-list'),
                index = parent_option.length,
                new_index = '[options]['+index+']';

            var new_option = '<div class="cn-option-list">'+parent_option.html().replace(/\[options\]\[(.*?)\]/g, new_index)+'</div>';

            var new_content = $(new_option);
            new_content.find('textarea, input').each(function(){
                $(this).val('');
            });
            new_content.insertBefore( $(this).prev() );
        });

        //Remove option
        $(document).on( 'click', '.cn-remove-option', function(){
            var option_list = $(this).parents('.cn-tabs-content').find('.cn-option-list'),
                index = option_list.index( $(this).parent() );

            if( index == option_list.length-1 ) {
                option_list.last().remove();
                return;
            }

            option_list.each(function(i){
                if( index == i ) {
                    $(this).remove();
                }
                if( i > index ){
                    var new_index = '[options]['+(i-1)+']',
                        new_list = $(this).html().replace(/\[options\]\[(.*?)\]/g, new_index);
                    $(this).html(new_list);
                }
            });
        });

        //Reset settings
        $(document).on( 'click', 'input#reset_cookie_notice_options', function () {
            return confirm( cn_args.message );
        });

    });
})( jQuery );
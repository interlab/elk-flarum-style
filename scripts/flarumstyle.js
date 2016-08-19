
;(function($){
    $(document).ready(function(){
        $('#flarum-menu a').click(function(event){

            event.preventDefault();

            ajax_indicator(true);
            var $a = $(this);
            var href = this.href;
            if (href.endsWith('action=boardindex')) {
                $.get(elk_scripturl + '?action=boardindex', {}, function(data) {
                    var html = $($.parseHTML(data)).find('#main_content_section').parent();
                    $('#wrapper').replaceWith(html);
                });

                ajax_indicator(false);
                return false;
            }

            var board_id = href.match(/board=(\d+\.\d+)/i);
            var sel = $('#flarum-select-sort :selected').val();
            if (board_id) {
                var url = elk_scripturl + '?action=flarumstyle;sa=ajax;gettopics=' + sel + ';board=' + board_id[1];
            } else {
                var url = elk_scripturl + '?action=flarumstyle;sa=ajax;gettopics=' + sel;
            }
            var params = {};

            $('#flarum-menu a').removeClass('flarum-bold').css('color', '').addClass('flarum-menu-default-a-color');

            // var color = $a.parent().find('span').css("background-color");
            var color = $a.attr('data-flarum-board-color');
            $a.addClass('flarum-bold').css("color", color);


            // console.log(sel, board_id, url);
            // return false;

            $.get(url, params, function(data) {
                $('div.flarum-topics-body').html(data);
                // console.log("Load was performed.");
            })
            .done(function() {
                // console.log("second success");
            })
            .fail(function() {
                // console.log("error");
                $('#flarum-errorbox-topics').show().html('<div class="errorbox">Error loading data.</div>');
            })
            .always(function() {
                // console.log("finished");
                ajax_indicator(false);
            });

            return false;
        });

        $('#flarum-select-sort').change(function(event){
            ajax_indicator(true);
            var sel = $(this).find(':selected').val();
            var board_id = $('#flarum-menu a.flarum-bold').attr('data-flarum-board-id');
            if (board_id) {
                var url = elk_scripturl + '?action=flarumstyle;sa=ajax;gettopics=' + sel + ';board=' + board_id;
            } else {
                var url = elk_scripturl + '?action=flarumstyle;sa=ajax;gettopics=' + sel;
            }
            var params = {};

            $.get(url, params, function(data) {
                $('div.flarum-topics-body').html(data);
            })
            .done(function() {
            })
            .fail(function() {
                $('#flarum-errorbox-topics').show().html('<div class="errorbox">Error loading data.</div>');
            })
            .always(function() {
                ajax_indicator(false);
            });
        });
    });
})(jQuery);

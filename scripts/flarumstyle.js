
;(function($){
    $(document).ready(function(){
        $('#flarum-start-discussion').click(function(event){
            event.preventDefault();

            // var $a = $(this);
            var href = this.href;

            if (href != elk_scripturl) {
                location.href = href;
            }
            // redirect to first board
            else {
                var f = $('#flarum-menu a[data-flarum-board-id]').first();
                if (!f) {
                    alert('Error: not find id board.');
                    return false;
                }
                var id_board = parseInt(f.attr('data-flarum-board-id'), 10);
                location.href = elk_scripturl + '?action=post;board=' + id_board.toFixed(1);
            }
            return false;
        });

        $('#flarum-menu a').click(function(event){
            event.preventDefault();

            ajax_indicator(true);
            var $a = $(this);
            var href = this.href;
            if (href.endsWith('action=boardindex')) {
                $.get(elk_scripturl + '?action=boardindex', {}, function(data) {
                    var html = $($.parseHTML(data)).find('#main_content_section');
                    $('#wrapper #main_content_section').replaceWith(html);
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

            var $newtopic = $('#flarum-start-discussion');
            if (board_id) {
                if (color) {
                    $newtopic.removeClass('flarum-start-discussion-def-bcolor').css({'background-color': color, 'color': '#fff'});
                } else {
                    $newtopic.css({'background-color': '', 'color': ''}).addClass('flarum-start-discussion-def-bcolor');
                }
                $newtopic.attr('href', elk_scripturl + '?action=post;board=' + board_id[1]);
            } else {
                $newtopic.css({'background-color': '', 'color': ''}).addClass('flarum-start-discussion-def-bcolor');
            }

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

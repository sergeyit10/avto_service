//флаг. если true, значит выполняется запрос. 
//нужен для защиты от повторного запроса на сервер
var request_send = false;
$(document).ready(function () {
    $('#search_form').submit(function (e) {
        if(request_send)return false;
        var form = $(this);
        clean_errors();
        e.preventDefault();
        
        $.ajax({
            url: "get_product.php",
            timeout: 20000,
            type: "post",
            dataType: 'json',
            beforeSend: function () {
                form.find('[type="submit"]',true);
                $('#product_list').html('').addClass('loading');
                request_send = true;
            },
            complete: function () {
                form.find('[type="submit"]',false);
                $('#product_list').removeClass('loading');
                request_send = false;
            },
            data: {
                "title": form.find('[name="title"]').val()
            },
            success: function (data) {
                if (data.errors) {
                    data.errors.forEach(function (text) {
                        print_error(text);
                    });
                    $('#product_list').html(print_error(data.errors));
                } else if (data.body) {
                    $('#product_list').html(data.body);
                    init_hide_rows();
                } else {
                    print_error('Ошибка сервиса. Попробуйте сделать запрос позже...');
                }
            },
            error: function () {
                print_error('Ошибка сервиса. Попробуйте сделать запрос позже...');
            }
        });
    });

});
//удалить все сообщеия об ошибках      
function clean_errors() {
    $('#product_list .error').remove();
}
//вывсести ошибку
function print_error(text) {
    $('#product_list').append('<p class="error">' + text + '</p>');
}
//скрывает строки с товарами, если их больше 3 
function init_hide_rows() {
    $('table.rows').each(function () {
        var table = $(this);
        var trs = table.find('tr');
        if (trs.length > 3) {
            var i = 0;
            trs.each(function () {
                if (i > 3) {
                    $(this).addClass('toggle_rows').hide();
                }
                i++;
            });
            var show_all_row_btn = $('<a class="show_all_row_btn" href="#">Показать остальные</a>');
            show_all_row_btn.click(function (e) {
                e.preventDefault();
                table.find('.toggle_rows').toggle();
                if (table.find('.toggle_rows:visible').length) {
                    $(this).html('Скрыть');
                } else {
                    $(this).html('Показать остальные');
                }
            });
            $(this).after($('<p class="a_center">').append(show_all_row_btn));
        }
    });
}



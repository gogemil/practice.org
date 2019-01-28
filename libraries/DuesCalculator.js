function str_replase(data) {
    var str = data;
    return str.toString().replace(/,/g,"");
}

function formatDollar(num) {
    var p = num.toFixed(2).split(".");
    return p[0].split("").reverse().reduce(function(acc, num, i, orig) {
            return  num + (i && !(i % 3) ? "," : "") + acc;
        }, "") + "." + p[1];
}

function coop_and_comm_year_2(annual_revenues, access_lines) {
    if(annual_revenues != 0) {
        var revenue_portion = annual_revenues * 0.00428;
        var total_revenue_portion = revenue_portion;
    }else {
        var revenue_portion = 20000;
        var total_revenue_portion = 20000;
    }

    var access_lines_assessment = 0.133 * access_lines;

    if (revenue_portion > 20000) {
        total_revenue_portion = 20000;
    }

    if ((total_revenue_portion > (2.66 * access_lines)) && annual_revenues != 0) {
        total_revenue_portion = 2.66 * access_lines;
    }
    var total_dues = total_revenue_portion + access_lines_assessment;
    if(total_dues < 500) {
        total_dues = 500;
    }
    return total_dues;
}

function comm_year_1(annual_revenues, access_lines) {
    if(annual_revenues != 0) {
        var revenue_portion = annual_revenues * 0.00374;
        var total_revenue_portion = revenue_portion;
    }else {
        var revenue_portion = 17500;
        var total_revenue_portion = 17500;
    }
    if (revenue_portion > 17500) {
        total_revenue_portion = 17500;
    }
    var access_lines_assessment = 0.116 * access_lines;
    if (revenue_portion < 500) {
        total_revenue_portion = 500;
        access_lines_assessment = 0;
    }
    if ((total_revenue_portion > (2.33 * access_lines)) && annual_revenues != 0) {
        total_revenue_portion = 2.33 * access_lines;
    }
    var total_dues = total_revenue_portion + access_lines_assessment;
    if(total_dues < 500) {
        total_dues = 500;
    }
    return total_dues;
}

// need to wrap in DOM loaded because all the js is loaded at the end
document.addEventListener('DOMContentLoaded', function() {
    (function ($) {
        $(document).ready(function() {
            $('.calculate').on('click', function(event) {
                var member_type = $('input[name=member_type]:checked').val();
                var annual_revenues = $('input[name=annual_revenues]').val();
                if (annual_revenues=="") {
                    $('.results').html('Please enter a value for Annual Revenues');
                    return true;
                }
                annual_revenues = parseFloat(str_replase(annual_revenues));
                var access_lines = $('input[name=access_lines]').val();
                access_lines = parseInt(str_replase(access_lines));
                if (!access_lines) {
                    $('.results').html('Please enter a value for Access Lines');
                    return true;
                }
                var output = '';

                if (member_type == 'cooperative') {
                    output += 'Proposed dues: $' + formatDollar(coop_and_comm_year_2(annual_revenues, access_lines)) + '<br>';
                } else {
                    output += 'First year dues: $' + formatDollar(comm_year_1(annual_revenues, access_lines)) + '<br><font color="#0d8017">';
                    output += 'Second year dues: $' + formatDollar(coop_and_comm_year_2(annual_revenues, access_lines)) + '<br>';
                }

                output = '<strong>Results:</strong><br>' + output;
                /*+ 'The minimal annual dues is $500 for ILEC members.';*/

                $('.results').html(output);

//                if (this.get('checked')) {
//                    $$('.main_category_' + main_category_id).value('checked', true);
//                }
            });
        }); // end of document.ready
    })(jQuery);
}, false);

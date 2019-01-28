// need to wrap in DOM loaded because all the js is loaded at the end
document.addEventListener('DOMContentLoaded', function() {	
    (function ($) {
        $(document).ready(function() {
            $('.calculate').on('click', function(event) {
            var member_type = $('input[name=member_type]:checked').val();
			var ilec = $('input[name=ilec]').val();

			if(isNaN(parseFloat(str_replase(ilec))) || ilec == "" || ilec == "0") {
				ilec = 0;
			}
			if(ilec != "0") {
				ilec = parseFloat(str_replase(ilec));
			}

			var dues_2017 = $('input[name=dues_2017]').val();
			var clec = $('input[name=clec]').val();

			if (dues_2017 == "") {
				$('.results').text('Wrong input values');
				return true;
			}
			if (clec == "") {
				$('.results').text('Wrong input values');
				return true;
			}

			if(ilec != "" || ilec != "0") {
				ilec = parseFloat(str_replase(ilec));
			}
			dues_2017 = parseFloat(str_replase(dues_2017));
			clec = parseFloat(str_replase(clec));

			var output = '';

			var dues_2018 = formula(ilec, dues_2017, clec, false, false);
			dues_2018 = parseFloat(str_replase(dues_2018));
			var dues_2019 = formula(ilec, dues_2017, clec, dues_2018, false);
			dues_2019 = parseFloat(str_replase(dues_2019));

			if (member_type == 'cooperative') {
				output += 'Year 1 Proposed Dues amount: $' + formatDollar(formula(ilec, dues_2017, clec, false, false)) + '<br>';
				output += 'Year 2 Proposed Dues amount: $' + formatDollar(formula(ilec, dues_2017, clec, dues_2018, false)) + '<br>';
				output += 'Year 3 Proposed Dues amount: $' + formatDollar(formula(ilec, dues_2017, clec, false, dues_2019)) + '<br>';
			}

            output = '<strong>Results:</strong><br>' + output;
            /*+ 'The minimal annual dues is $500 for ILEC members.';*/

            $('.results').html(output);
            });
        }); // end of document.ready
    })(jQuery);
}, false);

function str_replase(data) {
	var str = data;
	return str.toString().replace(/,/g,".");
}
	
function formatDollar(num) {
	num = parseFloat(num);
	var p = num.toFixed(2).split(".");
	return p[0].split("").reverse().reduce(function(acc, num, i, orig) {
		return  num + (i && !(i % 3) ? "," : "") + acc;
	}, "") + "." + p[1];
}
	
function formula(ilec, dues_2017, clec, dues_2018, dues_2019) {
	var ilec_new = ilec * 4.90;
	if(ilec_new == 0) {
		ilec_new = 25000;
	}
		
	if(ilec > 0) {
		if (dues_2018 === false && dues_2019 === false) {
		  	var dues_2017_new = dues_2017 * 1.15;
		} else if (dues_2018 !== false && dues_2019 === false) {
		  	var dues_2018_new = dues_2018 * 1.15;
		} else if (dues_2018 === false && dues_2019 !== false) {
		  	var dues_2019_new = dues_2019 * 1.15;
		}

		var greater_value = Math.max(ilec_new.toFixed(2), dues_2017);
		var sub_total = greater_value;

		if (dues_2018 === false && dues_2019 === false) {
		  	if(dues_2017_new > 0) {
				sub_total = Math.min(greater_value.toFixed(2), dues_2017_new.toFixed(2));
			}
		} else if (dues_2018 !== false && dues_2019 === false) {
		  	if(dues_2018_new > 0) {
				sub_total = Math.min(greater_value.toFixed(2), dues_2018_new.toFixed(2));
			}
		} else if (dues_2018 === false && dues_2019 !== false) {
		  	if(dues_2019_new > 0) {
				sub_total = Math.min(greater_value.toFixed(2), dues_2019_new.toFixed(2));
			}
		}

		if(sub_total > 25000) {
			sub_total = 25000;
		}
		if(sub_total < 500) {
			sub_total = 500;
		}
	}else {
		if (dues_2018 === false && dues_2019 === false) {
		 	var dues_2017_new = dues_2017;
		} else if (dues_2018 !== false && dues_2019 === false) {
		  	var dues_2018_new = dues_2018;
		} else if (dues_2018 === false && dues_2019 !== false) {
		  	var dues_2019_new = dues_2019;
		}

		sub_total = 25000;
	}
	 
	var clec_new = 0;
	if(clec != 0 || clec != '') {
		clec_new = clec * 0.25;
	}
	if(clec_new > 1000) {
		clec_new = 1000;
	}

	var total = sub_total + clec_new;

	if(total < 500) {
		//total = 500;
	}
	if(total > 25000) {
		//total = 25000;
	}

	return total.toFixed(2);
}

function onlyNumbersWithDot(e) {           
    var charCode;
    if (e.keyCode > 0) {
        charCode = e.which || e.keyCode;
    }
    else if (typeof (e.charCode) != "undefined") {
        charCode = e.which || e.keyCode;
    }
    if (charCode == 46)
        return true
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
}


$(function () {
	$.nette.init();
	$.nette.ext({success: function (payload) {
			if (payload.success) {
				$("#frm-courseReservation2").hide();
				info.appendTo($active);
			}
		}});
	$("#accordion").tooltip({
		track: true
	});
	$("#accordion").accordion({
		collapsible: true,
		active: false,
		heightStyle: "content"
	});

	$(function () {
		$("#dialog").dialog({
			buttons: {
				Ok: function () {
					$(this).dialog("close");
				}},
			modal: true,
			resizable: false,
			width: 760,
			autoOpen: false
		});

		$("#term").click(function () {
			$("#dialog").dialog("open");
		});
	});

	$("#tabs").tabs();

	$("button, input[type='submit']").button();
//		console.log($("#frmcourseReservation2-invoice"));

	$("#frm-courseReservation2-invoice").click(function () {
		if ($(this).is(':checked')) {
			$(".invoice").show();
		} else {
			$(".invoice").hide();
		}
	});
	var info;
	$("button.registration").click(function () {
		$(this).removeClass("ui-state-focus ui-state-hover");
		if (info) {
			$("#frm-courseReservation2").hide();
			info.appendTo($active);
		}
		$form = $("#frm-courseReservation2").detach();
		$active = $(this).parents(".ui-accordion-content");
		info = $active.children().detach();
		$form.appendTo($active);
		$("#frm-courseReservation2").show();
		$("input[name='course']").attr("value", $active.attr("courseId"));
	});
	$("button.storno").click(function (event) {
		event.preventDefault();
		event.stopPropagation();
		if (info) {
			$("#frm-courseReservation2").hide();
			info.appendTo($active);
			info = 0;
		}
	});
});

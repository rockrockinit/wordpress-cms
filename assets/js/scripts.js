(function ($) {

$(function ($) {
    /**
     * CMS ids component
     */
	$('#modal-cms-ids').on('show.bs.modal', function (e) {
		cms.modal = $(e.relatedTarget).closest('.cms-ids');
	});

    $('.cms-ids').on('click', '.btn-remove', function (e) {
        var $btn = $(this),
            $tr = $btn.closest('tr'),
			id = $tr.data('id'),
            $table = $tr.closest('table'),
			$component = $table.closest('.cms-ids'),
			$ids = $('.ids', $component),
			ids = $ids.val().split(/,|\|/),
			index = ids.indexOf(String(id));

        $tr.fadeOut(function () {
            $tr.remove();

            if (index > -1) {
                ids.splice(index, 1);
                $ids.val(ids.join(','));
            }

            if (!$('tbody tr', $table).length) {
                $frag = $('<tr class="no-records"></tr>');

                $('th', $table).each(function () {
                	$frag.append('<td></td>');
				});

                $('tbody', $table).append($frag);
            }
        });
    });

    // Make diagnosis table sortable
	if ($.fn.sortable) {
        $(".cms-ids .table-items > tbody").sortable({
            classes: {
                'ui-sortable-helper': 'cms-ids-helper'
            },
            helper: function (e, tr) {
                var $originals = tr.children(),
                    $helper = tr.clone();

                $helper.children().each(function (index) {
                    $(this).width($originals.eq(index).width());
                });

                return $helper;
            },
			beforeStop: function () {},
            stop: function (event, ui) {
            	var $component = $(this).closest('.cms-ids'),
					$table = $('.table-items', $component),
					ids = [];

            	$('tbody [data-id]', $table).each(function () {
            		ids.push($(this).data('id'));
				});

            	$('.ids', $component).val(ids.join(','));
            }
        }).disableSelection();
    }


	/**
	 * Data entry helpers.
	 */
	$("input[data-column-type='datetime']").mask("9999-99-99 99:99:99", { placeholder:"yyyy-mm-dd hh:mm:ss" } );
	$("input[data-column-type='datetime']").datetimepicker( { dateFormat: 'yy-mm-dd', timeFormat: 'HH:mm:ss' } );
	$("input[data-column-type='date']").datepicker({ dateFormat: 'yy-mm-dd' });
	$("input[data-column-type='date']").mask("9999-99-99", { placeholder:"yyyy-mm-dd" } );
	$("input[data-column-type='time']").mask("99:99:99", { placeholder:"hh:mm:ss" } );
	$("input[data-column-type='time']").timepicker( { timeFormat: 'HH:mm:ss', timeOnly: true } );
	$("input[data-column-type='year']").mask("9999");


	/**
	 * Schema editing.
	 */
	$(document.body).on("keyup blur", "input.schema-identifier", function() {
		$(this).val($(this).val().replace(/[^a-zA-Z0-9_ ]/g,'')).change();
		$(this).val($(this).val().replace(/ /g,'_')).change();
		$(this).val($(this).val().toLowerCase());
	});

	$("form.cms-schema .btn-add-column").click(function() {
		var $tr = $(this).parents("form").find("table.column-definitions tr:last");
		var $newTr = $tr.clone();
		$newTr.find("input").val("").prop("checked", false);
		$newTr.find("option").prop("selected", false);
		$newTr.find("input, select").each(function(){
			// Rename all form element names.
			var colNum = $("form.cms-schema table.column-definitions tr").length;
			var oldName = $(this).attr("name");
			var newName = oldName.replace(/columns\[.*\]\[(.*)\]/, "columns["+colNum+"][$1]");
			$(this).attr("name", newName);

			if ($(this).attr('size') == '30') {
				$(this).attr('size', 6);
			}
		});
		$tr.after($newTr);
		$newTr.find("[name*=name]").focus();
	});

	$(document).on('change', "form.cms-schema select[name*='xtype']", function() {
		var xtype = $(this).val();
		var $size = $(this).parents("tr").find("input[name*='size']");
		var $targetTable = $(this).parents("tr").find("select[name*='target_table']");
		var $autoInc = $(this).parents("tr").find("input[name='auto_increment']");

		$size.attr('size', 6);

		if (xtype === 'fk') {
			$size.prop("disabled", true);
			$targetTable.prop("disabled", false).prop("required", true);
			$autoInc.prop("disabled", true);
		} else if (xtype === 'integer') {
			$size.prop("disabled", false).prop("required", true);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", false);
		} else if (xtype === 'decimal') {
			$size.prop("disabled", false).prop("required", true);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		} else if (xtype === 'boolean') {
			$size.prop("disabled", true);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		} else if (xtype === 'text_short') {
			$size.prop("disabled", false).prop("required", true);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		} else if (xtype === 'text_long') {
			$size.prop("disabled", true);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		} else if (xtype === 'date') {
			$size.prop("disabled", true);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		} else if (xtype === 'enum' || xtype === 'set') {
			$size.attr('size', 30).prop("disabled", false);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		} else if (xtype === 'point') {
			$size.prop("disabled", true);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		} else {
			$size.prop("disabled", false);
			$targetTable.prop("disabled", true);
			$autoInc.prop("disabled", true);
		}
	});

	$("form.cms-schema select[name*='xtype']").change();

	$('form.cms-schema').on('click', 'a.move', function() {
		var $tr = $(this).parents("tr");

		$tr.hide();

		if ($(this).hasClass("move-up")) {
			$tr.prev("tr").before($tr);
		}

		if ($(this).hasClass("move-down")) {
			$tr.next("tr").after($tr);
		}

		$tr.show("slow");
	});


	/**
	 * Make sure .disabled buttons are properly disabled.
	 */
	$("button.disabled").prop("disabled", true);


	/**
	 * Set up the bits that use WP_API.
	 * Make sure the WP-API nonce is always set on AJAX requests.
	 */
	if (typeof wpApiSettings !== 'undefined') {
		$.ajaxSetup({
			headers: { 'X-WP-Nonce': wpApiSettings.nonce }
		});


		/**
		 * Jump between tables.
		 */
		// Get the table list.
		$.getJSON(wpApiSettings.root + "cms/tables", function( tableNames ) {
			for ( var t in tableNames ) {
				var table = tableNames[t];
				var url = cms.admin_url + "&controller=table&table=" + table.value;
				var $li = $("<li><a href='" + url + "'>" + table.label + "</a></li>");
				$li.hide();
				$("#cms-quick-jump").append($li);
			}
		});

		// Show the table list.
		$("#cms-quick-jump label").click(function(event) {
			event.preventDefault();
			//event.stopPropagation();
			var $quickJump = $(this).parents("#cms-quick-jump");
			$quickJump.toggleClass('expanded');
			if ($quickJump.hasClass('expanded')) {
				$quickJump.find("li[class!='filter']").show();
				$quickJump.find("input").focus().keyup();
			} else {
				$quickJump.find("li[class!='filter']").hide();
			}
		});

		// Close the table list by clicking anywhere else.
		$(document).click(function(e) {
			if ($(e.target).parents('#cms-quick-jump').length == 0) {
				$('#cms-quick-jump.expanded label').click();
			}
		});

		// Filter the table list.
		$("#cms-quick-jump input").keyup(function() {
			var s = $(this).val().toLowerCase();
			$(this).parents("#cms-quick-jump").find("li[class!='filter']").each(function(){
				var t = $(this).text().toLowerCase();
				if (t.indexOf(s) == -1) {
					$(this).hide();
				} else {
					$(this).show();
				}
			});
		});


		/**
		 * Handle foreign-key select lists (autocomplete when greater than N options).
		 */
		$(".cms .foreign-key .form-control:input").each(function() {
			// Autocomplete.
			$(this).autocomplete({
				source: wpApiSettings.root + "cms/fk/" + $(this).data('fk-table'),
				select: function( event, ui ) {
					event.preventDefault();
					$(this).val(ui.item.label);
					$(this).closest(".foreign-key").find(".actual-value").val(ui.item.value);
					$(this).closest(".foreign-key").find(".input-group-addon").text(ui.item.value);
				}
			});
			// Clear actual-value if emptied.
			$(this).change(function(){
				if ($(this).val().length === 0) {
					$(this).closest(".foreign-key").find(".actual-value").val("");
					$(this).closest(".foreign-key").find(".input-group-addon").text("");
				}
			});
			// Clear entered text if no value was selected.
			$(this).on("blur", function() {
				if ($(this).closest(".foreign-key").find(".actual-value").val().length === 0) {
					$(this).val("");
				}
			});
		});

	} // if (typeof wpApiSettings !== 'undefined')


	/**
	 * Dynamically add new filters.
	 */
	var $addFilter = $('<a class="btn btn-default"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs">&nbsp; Add Filter</a>');
	$(".cms-filters td.buttons").append($addFilter);
	$addFilter.click(function () {
		var filterCount = $(this).parents("table").find("tr.cms-filter").size();
		$lastrow = $(this).parents("table").find("tr.cms-filter:last");
		$newrow = $lastrow.clone();
		$newrow.find("select, input").each(function () {
			var newName = $(this).attr("name").replace(/\[[0-9]+\]/, "[" + filterCount + "]")
			$(this).attr("name", newName);
		});
		$newrow.find("td:first").html("&hellip;and");
		$newrow.find("input[name*='value']").val("");
		$lastrow.after($newrow);
	});


	/**
	 * Change 'is one of' filters to multi-line text input box,
	 * and if over a certain length submit as a POST request.
	 */
	$(".cms-filters").on("change", "select[name*='operator']", function(){
		var $oldFilter = $(this).parents("tr").find("[name*='[value]']");
		var newType = $oldFilter.is("input") ? "textarea" : "input";
		var requiresMulti = ($(this).val() === 'in' || $(this).val() === 'not in');
		var $newFilter = $("<"+newType+" name='"+$oldFilter.attr("name")+"'/>");
		$newFilter.val($oldFilter.val());
		if ($oldFilter.is("input") && requiresMulti) {
			// If changing TO a multi-line value.
			$newFilter.attr("rows", 2);
			$oldFilter.replaceWith($newFilter);
		} else if ($oldFilter.is("textarea") && !requiresMulti) {
			// If changing AWAY FROM a multi-line value.
			$newFilter.attr("type", "text");
			$oldFilter.replaceWith($newFilter);
		}
	});

	// Fire change manually.
	$(".cms-filters select[name*='operator']").change();

	// Change the form method depending on the filter size.
	$(".cms-filters").on("change", "textarea", function(){
		if ($(this).val().split(/\r*\n/).length > 50) {
			// Switch to a POST request for long "is one of" filters.
			$(this).parents("form").attr("method", "post");
		} else {
			// Switch back to get for smaller counts.
			$(this).parents("form").attr("method", "get");
		}
	});

	// Fire keyup manually.
	$(".cms-filters textarea").change();

	// Change the controller, action, and page num of the form depending on which button was clicked.
	$(".cms-filters button").click(function(e) {
		$(this).parents("form").find("input[name='controller']").val($(this).data("controller"));
		$(this).parents("form").find("input[name='action']").val($(this).data("action"));
		$(this).parents("form").find("input[name='p']").val($(this).data("p"));
		$(this).parents("form").find("input[name='cms_p']").val($(this).data("p"));
	});


	/**
	 * Add 'select all' checkboxen to the grants' table.
	 */
	// Copy an existing cell and remove its checkbox's names etc.
	$copiedCell = $(".cms-grants td.capabilities:first").clone();
	$copiedCell.find("input").attr("name", "");
	$copiedCell.find("input").removeAttr("checked");

	// For each select-all cell in the top row.
	$(".cms-grants tr.select-all td.target").each(function(){
		$(this).html($copiedCell.html());
	});

	// For each select-all cell in the left column.
	$(".cms-grants td.select-all").each(function(){
		$(this).html($copiedCell.html());
	});

	// Change the colour of checked boxen.
	$("form.cms-grants label.checkbox input").on('change', function() {
		if ($(this).prop("checked")) {
			$(this).closest("label").addClass("checked")
		} else {
			$(this).closest("label").removeClass("checked")
		}
	}).change();

	// Handle the en masse checking and un-checking from the top row.
	$("tr.select-all input").click(function() {
		colIndex = $(this).closest("td").index() + 1;
		capability = $(this).data("capability");
		$cells = $(".cms-grants tbody td:nth-child(" + colIndex + ")");
		$boxen = $cells.find("input[data-capability='" + capability + "']");
		$boxen.prop("checked", $(this).prop("checked")).change();
	});

	// Handle the en masse checking and un-checking from the left column.
	$("td.select-all input").click(function() {
		rowIndex = $(this).closest("tr").index() + 1;
		capability = $(this).data("capability");
		$cells = $(".cms-grants tbody tr:nth-child(" + rowIndex + ") td");
		$boxen = $cells.find("input[data-capability='" + capability + "']");
		$boxen.prop("checked", $(this).prop("checked")).change();
	});


	/**
	 * Enable point-selection for the editing form field.
	 */
	$(".cms-record .point-column").each(function() {
		var $formField = $(this).find(":input");
		var attrib = 'Map data &copy; <a href="http://openstreetmap.org">OSM</a> contributors <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
		var centre = [-32.05454466592707, 115.74644923210144]; // Fremantle!
		var map = L.map($(this).attr("id")+"-map").setView(centre, 16);
		L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: attrib }).addTo(map);
		var marker;

		// If already has a value.
		if ($formField.val()) {
			omnivore.wkt.parse($formField.val()).eachLayer(function(m) {
				addMarker(m.getLatLng());
				marker.update();
			});
		}
		// On click. Dragging is handled below.
		map.on('click', function(e) {
			addMarker(e.latlng);
		});
		// Add a marker at the specified location.
		function addMarker(latLng) {
			if (map.hasLayer(marker)) {
				map.removeLayer(marker);
			}
			marker = L.marker(latLng, { clickable:true, draggable:true });
			marker.on("add", recordNewCoords).on("dragend", recordNewCoords);
			marker.addTo(map);
			map.panTo(marker.getLatLng());
		}
		function recordNewCoords(e) {
			var wkt = "POINT("+marker.getLatLng().lng+" "+marker.getLatLng().lat+")";
			$formField.val(wkt);
		}
	});
});
})(jQuery);
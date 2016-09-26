
il.Util.addOnLoad
(
	function()
	{
		$("#btn_structure_import_execute").click(
			function()
			{
				// Show load circle - remove button
				$("#btn_structure_import_execute").remove();
				$("#div_structure_import_wait").removeClass('hidden').show('drop');
			}
		);
	}
);
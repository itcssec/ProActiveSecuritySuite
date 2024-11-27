jQuery(document).ready(function($) {
    // Initialize DataTables
    var table = $('#wtc-ips-table').DataTable({
        'columnDefs': [
            { 'orderable': false, 'targets': -1 } // Disable sorting on the last column (checkbox column)
        ]
    });

    var selectedIds = []; // To store selected IDs
    var selectedIPs = []; // To store selected IPs

    // Handle 'Select All' checkbox
    $('#wtc-select-all').on('click', function(){
        if($(this).is(':checked')){
            // Fetch all IDs and IPs via AJAX
            $.ajax({
                url: wtc_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'wtc_get_all_ids_ips',
                    wtc_ips_tab_nonce: wtc_ajax_object.wtc_ips_tab_nonce
                },
                success: function(response){
                    if(response.success){
                        selectedIds = response.data.ids;
                        selectedIPs = response.data.ips;
                        // Check all checkboxes
                        $('.wtc-delete-checkbox').prop('checked', true);
                    } else {
                        alert('Failed to fetch all IDs and IPs.');
                    }
                }
            });
        } else {
            selectedIds = [];
            selectedIPs = [];
            $('.wtc-delete-checkbox').prop('checked', false);
        }
    });

    // Handle individual checkbox click
    $(document).on('click', '.wtc-delete-checkbox', function(){
        var id = $(this).val();
        var ip = $(this).data('ip');
        if($(this).is(':checked')){
            if(!selectedIds.includes(id)){
                selectedIds.push(id);
            }
            if(!selectedIPs.includes(ip)){
                selectedIPs.push(ip);
            }
        } else {
            selectedIds = selectedIds.filter(function(value){
                return value != id;
            });
            selectedIPs = selectedIPs.filter(function(value){
                return value != ip;
            });
            $('#wtc-select-all').prop('checked', false); // Uncheck 'Select All' if any checkbox is unchecked
        }
    });

    // Handle page change in DataTable
    table.on('draw', function(){
        // Update checkboxes based on selectedIds
        $('.wtc-delete-checkbox').each(function(){
            var id = $(this).val();
            if(selectedIds.includes(id)){
                $(this).prop('checked', true);
            } else {
                $(this).prop('checked', false);
            }
        });

        var totalRowsInTable = table.page.info().recordsTotal;
        // If all rows are selected, check 'Select All', else uncheck it
        if (selectedIds.length === totalRowsInTable){
            $('#wtc-select-all').prop('checked', true);
        } else {
            $('#wtc-select-all').prop('checked', false);
        }
    });

    // Handle Delete Selected
    $('#wtc-delete-selected').on('click', function() {
        if (selectedIds.length > 0) {
            if (confirm('Are you sure you want to delete the selected records from the database?')) {
                // Perform AJAX request to delete the selected records
                $.ajax({
                    url: wtc_ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wtc_delete_ips',
                        ids: selectedIds,
                        wtc_ips_tab_nonce: wtc_ajax_object.wtc_ips_tab_nonce
                    },
                    success: function(response) {
                        // Reload the page after successful deletion
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        // Display error message
                        console.error(xhr.responseText);
                        alert('An error occurred while deleting the records. Please try again.');
                    }
                });
            }
        } else {
            alert('Please select at least one record to delete.');
        }
    });

    // Handle Delete Selected from Cloudflare
    $('#wtc-delete-selected-cloudflare').on('click', function() {
        if (selectedIPs.length > 0) {
            if (confirm('Are you sure you want to delete the selected records from Cloudflare?')) {
                // Perform AJAX request to delete the selected records from Cloudflare
                $.ajax({
                    url: wtc_ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wtc_delete_ips_cloudflare',
                        ips: selectedIPs,
                        wtc_ips_tab_nonce: wtc_ajax_object.wtc_ips_tab_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            // Reload the page after successful deletion
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Display error message
                        console.error(xhr.responseText);
                        alert('An error occurred while deleting the records from Cloudflare. Please try again.');
                    }
                });
            }
        } else {
            alert('Please select at least one record to delete from Cloudflare.');
        }
    });
});

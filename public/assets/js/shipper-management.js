import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

$(document).ready(function() {
    // Listen for Shipper events
    window.Echo.channel('shippers')
        .listen('ShipperCreated', (e) => {
            addShipperToTable(e.shipper);
        })
        .listen('ShipperUpdated', (e) => {
            updateShipperInTable(e.shipper);
        })
        .listen('ShipperDeleted', (e) => {
            removeShipperFromTable(e.shipperId);
        });

    // Add Shipper
    $('#addShipperBtn').click(function() {
        resetForm();
        $('#shipperModal').modal('show');
    });

    // Edit Shipper
    $(document).on('click', '.edit-shipper', function() {
        const shipperId = $(this).data('shipper-id');
        fetchShipperData(shipperId);
    });

    // Delete Shipper
    $(document).on('click', '.delete-shipper', function() {
        const shipperId = $(this).data('shipper-id');
        if (confirm('Are you sure you want to delete this shipper?')) {
            deleteShipper(shipperId);
        }
    });

    // Save Shipper
    $('#saveShipperBtn').click(function() {
        const shipperId = $('#shipperId').val();
        if (shipperId) {
            updateShipper(shipperId);
        } else {
            createShipper();
        }
    });
});

function resetForm() {
    $('#shipperForm')[0].reset();
    $('#shipperId').val('');
}

function fetchShipperData(shipperId) {
    $.ajax({
        url: `/admin/shippers/${shipperId}`,
        method: 'GET',
        success: function(response) {
            populateForm(response.shipper);
            $('#shipperModal').modal('show');
        },
        error: function(xhr) {
            alert('Error fetching shipper data');
        }
    });
}

function populateForm(shipper) {
    $('#shipperId').val(shipper.id);
    $('#name').val(shipper.name);
    $('#email').val(shipper.email);
    $('#vehicle_number').val(shipper.shipper_profile.vehicle_number);
    $('#work_area').val(shipper.shipper_profile.work_area);
    $('#phone_number').val(shipper.shipper_profile.phone_number);
    $('#vehicle_type').val(
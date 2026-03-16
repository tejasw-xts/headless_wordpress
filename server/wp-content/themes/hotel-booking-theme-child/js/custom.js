jQuery(function ($) {
    $('.hotel-scroll-link').on('click', function (event) {
        var target = $(this.hash);

        if (!target.length) {
            return;
        }

        event.preventDefault();
        $('html, body').animate(
            {
                scrollTop: target.offset().top - 100
            },
            700
        );
    });

    $('#availability-form').on('submit.hotelChild', function (event) {
        var checkIn = $('#check-in').val();
        var checkOut = $('#check-out').val();
        var guests = $('#guests').val();
        var roomType = $('#room-type').val();
        var roomsUrl = (window.hotelBookingChild && hotelBookingChild.roomsUrl) ? hotelBookingChild.roomsUrl : '/?post_type=room';
        var separator = roomsUrl.indexOf('?') === -1 ? '?' : '&';
        var url;

        if (!checkIn || !checkOut) {
            return;
        }

        if (new Date(checkOut) <= new Date(checkIn)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        url = roomsUrl + separator + 'check_in=' + encodeURIComponent(checkIn) + '&check_out=' + encodeURIComponent(checkOut) + '&guests=' + encodeURIComponent(guests);

        if (roomType) {
            url += '&room_type=' + encodeURIComponent(roomType);
        }

        window.location.href = url;
    });

    $('.hotel-room-thumb').on('click.hotelRoomGallery', function () {
        var button = $(this);
        var mainImage = $('#hotel-room-main-image');
        var imageUrl = button.data('full-image');
        var altText = button.data('alt') || '';

        if (!mainImage.length || !imageUrl) {
            return;
        }

        $('.hotel-room-thumb').removeClass('is-active');
        button.addClass('is-active');
        mainImage.attr('src', imageUrl);
        mainImage.attr('alt', altText);
    });
});

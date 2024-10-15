jQuery(document).ready(function($) {
    // Function to fetch initial crypto prices from CoinCap
    function fetchCryptoPrices() {
        $.ajax({
            url: 'https://api.coincap.io/v2/assets?ids=' + cryptoPriceTable.coins,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                // Update table with new data
                var cnt = 0;
                response.data.forEach(function(coin) {
                    var price = coin.priceUsd ? '$' + parseFloat(coin.priceUsd).toFixed(2) : 'N/A';
                    var change = coin.changePercent24Hr ? formatMarketCap(coin.changePercent24Hr) : 'N/A';
                    var marketCap = coin.marketCapUsd ? '$' + formatMarketCap(coin.marketCapUsd).toLocaleString() : 'N/A';

                    // Find and update existing table rows
                    var $row = $('#crypto-price-table tbody tr[data-coin="' + coin.id + '"]');
                    if ($row.length > 0) {
                        $row.find('.price').text(price);
                        $row.find('.market-cap').text(marketCap);
                    } else {
                        if (cnt == 0){
                            // Create new row if not found
                            $('#crypto-price-table tbody').append(`
                                <tr style="background-color:${cryptoPriceTable.table_body_color}; color:${cryptoPriceTable.text_color} !important ; filter: brightness(100%);" data-coin="${coin.id}">
                                    <td class="cpt-table-cell">
                                        <img src="${cryptoPriceTable.logo_url}${coin.id}-logo.png" class="cpt-profile-image">
                                        <a style="text-decoration: none; color: inherit;" href="https://icogems.com/cryptocurrency"> <span class="cpt-name" style="font-weight: bold;">${coin.name}</span> </a>
                                    </td>
                                    <td class="cpt-table-cell"> <span class="price">${price}</span> ${checkChainge(change)} </td>
                                    ${cryptoPriceTable.show_marketcap == 'true' && `<td class="cpt-table-cell market-cap">${marketCap}</td>`}
                                </tr>
                            `);
                            cnt = 1;
                        }
                        else{
                            // Create new row if not found
                            $('#crypto-price-table tbody').append(`
                                <tr style="background-color:${cryptoPriceTable.table_body_color}; color:${cryptoPriceTable.text_color} !important ; filter: brightness(90%);" data-coin="${coin.id}">
                                    <td class="cpt-table-cell">
                                        <img src="${cryptoPriceTable.logo_url}${coin.id}-logo.png" class="cpt-profile-image">
                                        <a style="text-decoration: none; color: inherit;" href="https://icogems.com/cryptocurrency"> <span class="cpt-name" style="font-weight: bold;">${coin.name}</span> </a>
                                    </td>
                                    <td class="cpt-table-cell"> <span class="price">${price}</span> ${checkChainge(change)} </td>
                                    ${cryptoPriceTable.show_marketcap == 'true' && `<td class="cpt-table-cell market-cap">${marketCap}</td>`}
                                </tr>
                            `);
                            cnt = 0;
                        }                        
                    }
                });

                // Update table color
                var tableColor = getComputedStyle(document.documentElement).getPropertyValue('--crypto-price-table-color').trim();
                $('#crypto-price-table').css('background-color', tableColor);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching data:', status, error);
            }
        });
    }

    // Function to update prices from WebSocket
    function updatePricesFromWebSocket(data) {
        Object.keys(data).forEach(function(coinId) {
            var price = data[coinId] ? '$' + parseFloat(data[coinId]).toFixed(2) : 'N/A';

            // Find and update existing table rows
            var $row = $('#crypto-price-table tbody tr[data-coin="' + coinId + '"]');
            if ($row.length > 0) {
                $row.find('.price').text(price);
            }
        });
    }

    // Connect to CoinCap WebSocket
    var socket = new WebSocket('wss://ws.coincap.io/prices?assets=' + cryptoPriceTable.coins);

    socket.onopen = function(event) {
        console.log('WebSocket connection established');
    };

    socket.onmessage = function(event) {
        var data = JSON.parse(event.data);
        updatePricesFromWebSocket(data);
    };

    socket.onerror = function(event) {
        console.error('WebSocket error:', event);
    };

    socket.onclose = function(event) {
        console.log('WebSocket connection closed:', event);
    };

    // Fetch crypto prices initially when the page loads
    fetchCryptoPrices();

    // Update prices every 5 minutes (adjust interval as needed)
    setInterval(fetchCryptoPrices, 5 * 60 * 1000); // 5 minutes

    // Apply table color from settings
    var tableColor = getComputedStyle(document.documentElement).getPropertyValue('--crypto-price-table-color').trim();
    $('#crypto-price-table').css('background-color', tableColor);
});


function formatMarketCap(value) {
    if (value >= 1e12) {
        return (value / 1e12).toFixed(2) + ' T';
    } else if (value >= 1e9) {
        return (value / 1e9).toFixed(2) + ' B';
    } else if (value >= 1e6) {
        return (value / 1e6).toFixed(2) + ' M';
    } else if (value >= 1e3) {
        return (value / 1e3).toFixed(2) + ' K';
    } else {
        return value.toString();
    }
}


function checkChainge(value) {
    value = parseFloat(value);
    if (value >= 0) {
        return '<span style="color: green; font-size: 0.8rem;"> (' + value.toFixed(2) + '%) </span>' ;
    } 
    else if (value < 0) {
        return '<span style="color: red; font-size: 0.8rem;"> (' + value.toFixed(2) + '%) </span>' ;
    } 
    else {
        return value;
    }
}



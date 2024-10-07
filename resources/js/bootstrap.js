import 'bootstrap';

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo'
import io from 'socket.io-client'

window.io = io
window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: `${window.location.protocol}//${window.location.hostname}:6001`,
    transports: ['websocket', 'polling']
})
// Lắng nghe sự kiện 'connect'
window.Echo.connector.socket.on('connect', () => {
    console.log('Đã kết nối tới Laravel Echo Server');
});

// Lắng nghe sự kiện 'disconnect'
window.Echo.connector.socket.on('disconnect', () => {
    console.log('Đã ngắt kết nối khỏi Laravel Echo Server');
});

// Lắng nghe sự kiện 'error'
window.Echo.connector.socket.on('error', (error) => {
    console.error('Lỗi Laravel Echo:', error);
});


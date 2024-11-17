@extends('layouts.app')

@push('styles')
    <style>
        #users > li {
            cursor: pointer;
        }

        /* Style for the message list */
        #messages {
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-y: auto; 
            min-height: 45vh; 
            height: 45vh; 
            padding: 10px;
            background-color: #f5f5f5; 
            border-radius: 10px;
            flex-grow: 1; 
        }

        /* Style for each message */
        .message {
            max-width: 70%;
            padding: 10px;
            border-radius: 15px;
            margin: 5px 0;
            line-height: 1.4;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }

        /* Style for sender messages (right aligned) */
        .message.sent {
            background-color: #007bff; /* Blue background for sent messages */
            color: white;
            align-self: flex-end;
            text-align: right;
        }

        /* Style for receiver messages (left aligned) */
        .message.received {
            background-color: #e9ecef; /* Light gray background for received messages */
            color: #333;
            align-self: flex-start;
            text-align: left;
        }

        /* Style for the form */
        form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }

        /* Style for input field */
        #message {
            flex: 1;
            border-radius: 25px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            font-size: 16px;
        }

        /* Style for the send button */
        #send {
            border-radius: 25px;
            padding: 10px 20px;
            background-color: #28a745; /* Green background for send button */
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        #send:hover {
            background-color: #218838; /* Darker green on hover */
        }
        /* Style for user list */
        #users {
            padding: 0;
            list-style: none;
            border-left: 1px solid #dee2e6;
            background-color: #fff; /* White background for user list */
        }

        #users > li {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.3s ease;
        }

        #users > li:hover {
            background-color: #f8f9fa; /* Light gray background on hover */
        }
        .container {
            margin-top: 50px;
            margin-right: 50px;
        }
        .message.from-me {
            align-self: flex-end; /* Căn phải */
            text-align: right;
        }

        .message.to-me {
            align-self: flex-start; /* Căn trái */
            text-align: left;
        }
 </style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Chat') }}</div>

                <div class="card-body">
                    <div class="row p-2">
                        <div class="col-md-10">
                            <div class="border rounded-lg">
                                <ul id="messages" class="list-unstyled">
                                    <!-- Hiển thị các tin nhắn trước đó -->
                                    @foreach($messages as $message)
                                        <li class="message {{ $message->user_id == auth()->user()->id ? 'sent from-me' : 'received to-me' }}">
                                            {{ $message->user->name }}: {{ $message->message }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <form>
                                <input type="text" id="message" class="form-control" placeholder="Nhập tin nhắn...">
                                <button id="send" type="submit">Gửi</button>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <p><strong>Người dùng Online</strong></p>
                            <ul 
                                id="users"
                                class="list-unstyled overflow-auto text-info"
                                style="min-height: 45vh"
                            >
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script type="module">
    const usersElement = document.getElementById('users')
    const messagesElement = document.getElementById('messages')

    // Hàm cuộn xuống cuối danh sách tin nhắn
    function scrollToBottom() {
        messagesElement.scrollTop = messagesElement.scrollHeight;
    }

    // Cuộn xuống khi trang được tải
    scrollToBottom();

    Echo.join('chat')
        .here((users) => {
            users.forEach((user, index) => {
                const element = document.createElement('li')
                element.setAttribute('id', user.id)
                element.setAttribute('onclick', `greetUser("${user.id}")`)
                element.innerText = user.name
                usersElement.appendChild(element)
            })
        })
        .joining((user) => {
            const element = document.createElement('li')
            element.setAttribute('id', user.id)
            element.setAttribute('onclick', `greetUser("${user.id}")`)
            element.innerText = user.name
            usersElement.appendChild(element)
        })
        .leaving((user) => {
            const element = document.getElementById(user.id)
            element.parentNode.removeChild(element)
        })
        .listen('MessageSent', (e) => {
            const element = document.createElement('li')
            element.innerText = e.user.name + ': ' + e.message;

            // Kiểm tra xem tin nhắn có phải do người dùng hiện tại gửi hay không
            if (e.user.id === {{ auth()->user()->id }}) {
                element.classList.add('message', 'sent', 'from-me'); // Tin nhắn của tôi
            } else {
                element.classList.add('message', 'received', 'to-me'); // Tin nhắn của người khác
            }
            messagesElement.appendChild(element)

            // Cuộn xuống cuối khi có tin nhắn mới
            scrollToBottom();
        })
</script>

<script type="module">
    const messageElement = document.getElementById('message')
    const sendElement = document.getElementById('send')
    sendElement.addEventListener('click', (e) => {
        e.preventDefault();
        window.axios.post('/chat/message', {
            message: messageElement.value
        })
        messageElement.value = ""

        // Cuộn xuống cuối khi gửi tin nhắn mới
        scrollToBottom();
    });
</script>
<script>
    function greetUser(id){
        window.axios.post('/chat/greet/'+id);
    }
</script>
<script type="module">
    const messagesElement = document.getElementById('messages')
    Echo.private('chat.greet.{{auth()->user()->id}}')
        .listen('GreetingSent', (e) => {
            const element = document.createElement('li')
            element.innerText = e.message
            element.classList.add('text-success')
            messagesElement.appendChild(element)

            // Cuộn xuống cuối khi có tin nhắn mới
            scrollToBottom();
        })
</script>
@endpush
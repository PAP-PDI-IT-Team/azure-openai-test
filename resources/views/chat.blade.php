<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <style>
        body, main {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            width: 100%;
            gap: 20px;
            padding: 5px 20px;
        }

        .response {
            width: 80%;
            text-align: left;
        }

            /* Full-screen container */
        .spinner-container {
            visibility: hidden;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        /* Spinner */
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(0, 0, 0, 0.2);
            border-top-color: #000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Keyframe animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }


    </style>
</head>
<body>
    <!-- Spinner Container -->
    <div class="spinner-container">
        <div class="spinner"></div>
    </div>
    <main>
        <input type="text" name="prompt" id="prompt">
        <span id="response" class="response"></span>
        <button type="button" onclick="chatPrompt()" class="submit">Ask AI</button>
    </main>
    <script>
        async function chatPrompt() {
            try {
                activateSpinner();
                const responseP = document.getElementById('response');
                responseP.innerHTML = ''; // Clear previous content
                let inputPrompt = document.getElementById('prompt').value;
                let prompt = inputPrompt += " Make your response at least 2 paragraphs."

                const response = await fetch("{{ url('') }}/api/streamchat", {
                    method: "POST",
                    body: JSON.stringify({ prompt }),
                    headers: {
                        "Content-Type": "application/json",
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                // Streaming Response
                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                async function appendText(text) {
                    for (let char of text) {
                        responseP.innerHTML += char; // Append one character at a time
                        await new Promise(resolve => setTimeout(resolve, 10)); // Adjust speed
                    }
                }

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, { stream: true });
                    await appendText(chunk); // Append with typewriter effect
                }
            } catch (error) {
                console.error("Error streaming response:", error);
            } finally {
                deactivateSpinner();
            }
        }

        function activateSpinner() {
            const spinner = document.querySelector('.spinner-container')
            spinner.style.visibility = 'visible'
        }

        function deactivateSpinner() {
            const spinner = document.querySelector('.spinner-container')
            spinner.style.visibility = 'hidden'
        }
    </script>
</body>
</html>
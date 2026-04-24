<form>
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" placeholder="Enter your username" aria-label="Username" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" placeholder="Enter your password" aria-label="Password" required>

    <button type="submit">Login</button>
</form>

<div class="demo-credentials">
    <h3>Demo Credentials</h3>
    <p><strong>Username:</strong> demoUser</p>
    <p><strong>Password:</strong> demoPass</p>
</div>
<style>
    form {
        display: flex;
        flex-direction: column;
        max-width: 300px;
        margin: auto;
    }

    input {
        margin: 0.5em 0;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .demo-credentials {
        margin-top: 20px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #f9f9f9;
    }
</style>
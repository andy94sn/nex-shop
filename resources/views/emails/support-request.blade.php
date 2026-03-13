<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Cerere de suport nouă</title></head>
<body style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
    <h2>Cerere de suport nouă</h2>
    <table>
        <tr><td><strong>Nume:</strong></td><td>{{ $supportRequest->name }}</td></tr>
        <tr><td><strong>Email:</strong></td><td>{{ $supportRequest->email }}</td></tr>
        <tr><td><strong>Pagina sursă:</strong></td><td>{{ $supportRequest->page_source ?? '—' }}</td></tr>
    </table>
    <hr>
    <p><strong>Mesaj:</strong></p>
    <p>{{ $supportRequest->message }}</p>
</body>
</html>

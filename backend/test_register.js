const http = require('http');

const data = JSON.stringify({
  company_name: "Test Company",
  admin_name: "Test Admin",
  email: "test2@gmail.com",
  password: "password123",
  password_confirmation: "password123",
  phone: "1234567890",
  address: "Test Address",
  gst_no: "12345"
});

const options = {
  hostname: 'localhost',
  port: 8000,
  path: '/api/register-company',
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Content-Length': data.length,
    'Accept': 'application/json'
  }
};

const req = http.request(options, res => {
  console.log(`statusCode: ${res.statusCode}`);
  res.on('data', d => {
    process.stdout.write(d);
  });
});

req.on('error', error => {
  console.error(error);
});

req.write(data);
req.end();

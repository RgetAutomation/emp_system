const localtunnel = require('localtunnel');

(async () => {
  const tunnel = await localtunnel({ port: 5173, subdomain: 'emp-system-permanent-tunnel' });

  // the assigned public url for your tunnel
  // i.e. https://abcdefgjhij.loca.lt
  console.log('TUNNEL SUCCESS: ' + tunnel.url);

  tunnel.on('close', () => {
    // tunnels are closed
    console.log('Tunnel closed');
  });
})();

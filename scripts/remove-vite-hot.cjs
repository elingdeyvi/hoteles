const fs = require('fs');
const path = require('path');

const hot = path.join(__dirname, '..', 'public', 'hot');
if (fs.existsSync(hot)) {
    fs.unlinkSync(hot);
    console.log('Removed public/hot (Vite dev marker).');
}

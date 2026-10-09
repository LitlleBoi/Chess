// ========================================================
// SCHAAK ENGINE & SPELINTERACTIE (Level 3 SMART Leerdoel)
// Javascript: pionnen bewegen, geen verkeerde moves, om de beurt,
// pionnen opeten, schaakmat & pat detectie, klokken en chat.
// ========================================================

class ChessGame {
    constructor() {
        this.boardContainer = document.getElementById('chessboard');
        this.containerMeta = document.querySelector('.game-layout-container');
        if (!this.boardContainer || !this.containerMeta) return;

        this.userId = parseInt(this.containerMeta.dataset.userId, 10);
        this.username = this.containerMeta.dataset.username;
        this.opponentId = parseInt(this.containerMeta.dataset.opponentId, 10);
        this.opponentName = this.containerMeta.dataset.opponentName;
        this.timeSeconds = parseInt(this.containerMeta.dataset.timeSeconds, 10) || 600;

        // Klokken
        this.whiteTime = this.timeSeconds;
        this.blackTime = this.timeSeconds;
        this.timerInterval = null;

        // Spelstatus
        this.turn = 'w'; // 'w' (Wit / Jij) of 'b' (Zwart / Tegenstander)
        this.selectedSquare = null;
        this.validMoves = [];
        this.isGameOver = false;
        this.moveHistory = []; // Bevat { white: 'e4', black: 'e5' }

        // Koning en toren verplaatst flags voor rokade
        this.hasMoved = {
            wK: false, wR_a: false, wR_h: false,
            bK: false, bR_a: false, bR_h: false
        };
        this.enPassantSquare = null; // bijv. { r: 2, c: 4 }

        // Startopstelling: 8x8 matrix (0,0 = a8, 7,7 = h1)
        this.board = this.createInitialBoard();

        this.initAudio();
        this.initDOM();
        this.initChat();
        this.startClocks();
        this.renderBoard();
    }

    createInitialBoard() {
        return [
            ['br', 'bn', 'bb', 'bq', 'bk', 'bb', 'bn', 'br'], // rij 8
            ['bp', 'bp', 'bp', 'bp', 'bp', 'bp', 'bp', 'bp'], // rij 7
            [null, null, null, null, null, null, null, null], // rij 6
            [null, null, null, null, null, null, null, null], // rij 5
            [null, null, null, null, null, null, null, null], // rij 4
            [null, null, null, null, null, null, null, null], // rij 3
            ['wp', 'wp', 'wp', 'wp', 'wp', 'wp', 'wp', 'wp'], // rij 2
            ['wr', 'wn', 'wb', 'wq', 'wk', 'wb', 'wn', 'wr']  // rij 1
        ];
    }

    // Web Audio synthesizer voor schaakgeluiden (geen externe mp3 vereist)
    initAudio() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContext();
        } catch (e) {
            this.audioCtx = null;
        }
    }

    playSound(type = 'move') {
        if (!this.audioCtx) return;
        if (this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
        const osc = this.audioCtx.createOscillator();
        const gain = this.audioCtx.createGain();
        osc.connect(gain);
        gain.connect(this.audioCtx.destination);

        const now = this.audioCtx.currentTime;
        if (type === 'capture') {
            osc.frequency.setValueAtTime(320, now);
            osc.frequency.exponentialRampToValueAtTime(120, now + 0.12);
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.12);
            osc.start(now);
            osc.stop(now + 0.12);
        } else if (type === 'check') {
            osc.frequency.setValueAtTime(540, now);
            osc.frequency.exponentialRampToValueAtTime(680, now + 0.2);
            gain.gain.setValueAtTime(0.25, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
            osc.start(now);
            osc.stop(now + 0.2);
        } else {
            // Normale zet
            osc.frequency.setValueAtTime(420, now);
            osc.frequency.exponentialRampToValueAtTime(240, now + 0.08);
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.08);
            osc.start(now);
            osc.stop(now + 0.08);
        }
    }

    initDOM() {
        // Knoppen voor Opgeven en Gelijkspel
        const btnResign = document.getElementById('btn-resign');
        if (btnResign) {
            btnResign.addEventListener('click', () => {
                if (this.isGameOver) return;
                if (confirm('Weet je zeker dat je wilt opgeven?')) {
                    this.endGame('black', 'resigned', 'Je hebt opgegeven.');
                }
            });
        }

        const btnDraw = document.getElementById('btn-draw');
        if (btnDraw) {
            btnDraw.addEventListener('click', () => {
                if (this.isGameOver) return;
                // Bot accepteert gelijkspel indien positie neutraal of bij aanbod
                this.addChatMessage(this.opponentName, 'Ik accepteer je voorstel voor gelijkspel! Goed gespeeld.');
                this.endGame('draw', 'draw', 'Partij geëindigd in remise (gelijkspel).');
            });
        }

        // Rematch knop in modal
        const btnModalRematch = document.getElementById('btn-modal-rematch');
        if (btnModalRematch) {
            btnModalRematch.addEventListener('click', () => {
                window.location.reload();
            });
        }
    }

    initChat() {
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        if (!chatForm || !chatInput) return;

        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const msg = chatInput.value.trim();
            if (!msg) return;

            this.addUserChatMessage(msg);
            chatInput.value = '';

            // Bot antwoord na 1 seconde
            setTimeout(() => {
                this.botChatReply(msg);
            }, 900);
        });
    }

    addUserChatMessage(text) {
        const chatArea = document.getElementById('chat-messages');
        if (!chatArea) return;
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble user-bubble';
        bubble.innerHTML = `<span class="bubble-sender">Jij:</span><span class="bubble-text">${this.escapeHTML(text)}</span>`;
        chatArea.appendChild(bubble);
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    addChatMessage(sender, text) {
        const chatArea = document.getElementById('chat-messages');
        if (!chatArea) return;
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble opponent-bubble';
        bubble.innerHTML = `<span class="bubble-sender">${this.escapeHTML(sender)}:</span><span class="bubble-text">${this.escapeHTML(text)}</span>`;
        chatArea.appendChild(bubble);
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    botChatReply(userMsg) {
        const lower = userMsg.toLowerCase();
        let replies = [
            'Mooie zet!',
            'Even goed nadenken over deze positie... 🤔',
            'Scherpe tactiek van jou!',
            'Laten we kijken wat er nu gaat gebeuren.',
            'Goede partij tot nu toe!',
            'Pas op voor je stukken! 😉'
        ];
        if (lower.includes('hallo') || lower.includes('hoi')) {
            replies = ['Hoi! Veel succes.', 'Hallo! Klaar om te verliezen? 😉'];
        } else if (lower.includes('gg') || lower.includes('goed')) {
            replies = ['Zeker, mooi gespeeld!', 'Bedankt, jij ook!'];
        }
        const chosen = replies[Math.floor(Math.random() * replies.length)];
        this.addChatMessage(this.opponentName, chosen);
    }

    // Klokken logica
    startClocks() {
        clearInterval(this.timerInterval);
        this.timerInterval = setInterval(() => {
            if (this.isGameOver) {
                clearInterval(this.timerInterval);
                return;
            }

            if (this.turn === 'w') {
                this.whiteTime = Math.max(0, this.whiteTime - 1);
                this.updateClockDisplay('white-timer', this.whiteTime);
                if (this.whiteTime === 0) {
                    this.endGame('black', 'timeout', 'Tijd verstreken! Zwart wint op tijd.');
                }
            } else {
                this.blackTime = Math.max(0, this.blackTime - 1);
                this.updateClockDisplay('black-timer', this.blackTime);
                if (this.blackTime === 0) {
                    this.endGame('white', 'timeout', 'Tijd verstreken! Wit wint op tijd.');
                }
            }
        }, 1000);
    }

    updateClockDisplay(elementId, seconds) {
        const el = document.getElementById(elementId);
        if (!el) return;
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        
        const whiteTimer = document.getElementById('white-timer');
        const blackTimer = document.getElementById('black-timer');
        if (whiteTimer && blackTimer) {
            whiteTimer.classList.toggle('active', this.turn === 'w');
            blackTimer.classList.toggle('active', this.turn === 'b');
        }
    }

    // Bord tekenen
    renderBoard() {
        this.boardContainer.innerHTML = '';
        const inCheckSquare = this.isKingInCheck(this.turn) ? this.findKing(this.turn) : null;

        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const square = document.createElement('div');
                const isLight = (r + c) % 2 === 0;
                square.className = `chess-square ${isLight ? 'light' : 'dark'}`;
                square.dataset.r = r;
                square.dataset.c = c;

                // Geselecteerd veld highlighten
                if (this.selectedSquare && this.selectedSquare.r === r && this.selectedSquare.c === c) {
                    square.classList.add('selected');
                }

                // Schaak highlighten op koning
                if (inCheckSquare && inCheckSquare.r === r && inCheckSquare.c === c) {
                    square.classList.add('in-check');
                }

                // Legale zetten markeren
                const isMoveTarget = this.validMoves.some(m => m.r === r && m.c === c);
                if (isMoveTarget) {
                    if (this.board[r][c] || (this.enPassantSquare && this.enPassantSquare.r === r && this.enPassantSquare.c === c)) {
                        square.classList.add('capture-target');
                    } else {
                        square.classList.add('move-target');
                    }
                }

                // Stuk renderen
                const piece = this.board[r][c];
                if (piece) {
                    const img = document.createElement('img');
                    img.src = `../image/pieces/${piece}.svg`;
                    img.alt = piece;
                    img.className = 'chess-piece';
                    square.appendChild(img);
                }

                square.addEventListener('click', () => this.handleSquareClick(r, c));
                this.boardContainer.appendChild(square);
            }
        }
    }

    // Klikken op een schaakveld
    handleSquareClick(r, c) {
        if (this.isGameOver) return;
        if (this.turn !== 'w') return; // Alleen speler (wit) mag nu zetten

        const clickedPiece = this.board[r][c];

        // 1. Als er al een stuk geselecteerd was en geklikt veld is een legale zet
        if (this.selectedSquare) {
            const move = this.validMoves.find(m => m.r === r && m.c === c);
            if (move) {
                this.executeMove(this.selectedSquare.r, this.selectedSquare.c, r, c);
                this.selectedSquare = null;
                this.validMoves = [];
                this.renderBoard();
                return;
            }
        }

        // 2. Selecteer eigen stuk (wit)
        if (clickedPiece && clickedPiece.startsWith('w')) {
            this.selectedSquare = { r, c };
            this.validMoves = this.getLegalMoves(r, c, this.board, this.turn);
        } else {
            this.selectedSquare = null;
            this.validMoves = [];
        }

        this.renderBoard();
    }

    // Zet uitvoeren
    executeMove(fromR, fromC, toR, toC, isBot = false) {
        const piece = this.board[fromR][fromC];
        const targetPiece = this.board[toR][toC];
        const isCapture = !!targetPiece;

        // Notatie genereren (bijv. e4, Pf3, Lxf7+)
        const san = this.generateSAN(fromR, fromC, toR, toC, piece, targetPiece);

        // Rokade behandeling
        if (piece === 'wk' && fromR === 7 && fromC === 4) {
            if (toC === 6) { // Kort
                this.board[7][5] = 'wr';
                this.board[7][7] = null;
            } else if (toC === 2) { // Lang
                this.board[7][3] = 'wr';
                this.board[7][0] = null;
            }
        }
        if (piece === 'bk' && fromR === 0 && fromC === 4) {
            if (toC === 6) { // Kort
                this.board[0][5] = 'br';
                this.board[0][7] = null;
            } else if (toC === 2) { // Lang
                this.board[0][3] = 'br';
                this.board[0][0] = null;
            }
        }

        // En passant capture
        if ((piece === 'wp' || piece === 'bp') && this.enPassantSquare && toR === this.enPassantSquare.r && toC === this.enPassantSquare.c) {
            const capR = piece === 'wp' ? toR + 1 : toR - 1;
            this.board[capR][toC] = null;
        }

        // Zet verplaatsen
        this.board[toR][toC] = piece;
        this.board[fromR][fromC] = null;

        // Promotie naar Koningin
        if (piece === 'wp' && toR === 0) {
            this.board[toR][toC] = 'wq';
        } else if (piece === 'bp' && toR === 7) {
            this.board[toR][toC] = 'bq';
        }

        // En passant markering instellen
        if (piece === 'wp' && fromR === 6 && toR === 4) {
            this.enPassantSquare = { r: 5, c: fromC };
        } else if (piece === 'bp' && fromR === 1 && toR === 3) {
            this.enPassantSquare = { r: 2, c: fromC };
        } else {
            this.enPassantSquare = null;
        }

        // Rokade vlaggen bijwerken
        if (piece === 'wk') this.hasMoved.wK = true;
        if (piece === 'bk') this.hasMoved.bK = true;
        if (fromR === 7 && fromC === 0) this.hasMoved.wR_a = true;
        if (fromR === 7 && fromC === 7) this.hasMoved.wR_h = true;
        if (fromR === 0 && fromC === 0) this.hasMoved.bR_a = true;
        if (fromR === 0 && fromC === 7) this.hasMoved.bR_h = true;

        // Geluidseffect
        const nextTurn = this.turn === 'w' ? 'b' : 'w';
        const isCheck = this.isKingInCheck(nextTurn);
        if (isCheck) {
            this.playSound('check');
        } else if (isCapture) {
            this.playSound('capture');
        } else {
            this.playSound('move');
        }

        // Zet opslaan in geschiedenis
        this.recordMove(san);

        // Beurt wisselen
        this.turn = nextTurn;

        // Controleren op schaakmat of pat
        const hasMovesLeft = this.hasAnyLegalMoves(this.turn);
        if (!hasMovesLeft) {
            if (this.isKingInCheck(this.turn)) {
                const winner = this.turn === 'w' ? 'black' : 'white';
                const msg = winner === 'white' ? 'Schaakmat! Wit heeft gewonnen! 🏆' : 'Schaakmat! Zwart heeft gewonnen.';
                this.endGame(winner, 'checkmate', msg);
                return;
            } else {
                this.endGame('draw', 'draw', 'Pat! Geen geldige zetten meer. Gelijkspel.');
                return;
            }
        }

        // Als het nu zwart is, laat bot reageren
        if (this.turn === 'b' && !isBot) {
            this.scheduleBotMove();
        }
    }

    // Zetgeschiedenis bijhouden & weergave in tabel (Figma Match)
    recordMove(san) {
        if (this.turn === 'w') {
            this.moveHistory.push({ white: san, black: '' });
        } else {
            if (this.moveHistory.length > 0) {
                this.moveHistory[this.moveHistory.length - 1].black = san;
            }
        }
        this.renderMoveHistory();
    }

    renderMoveHistory() {
        const historyContainer = document.getElementById('move-history-list');
        if (!historyContainer) return;

        if (this.moveHistory.length === 0) {
            historyContainer.innerHTML = '<div class="moves-placeholder">Nog geen zetten gedaan.</div>';
            return;
        }

        let html = '';
        this.moveHistory.forEach((move, idx) => {
            html += `
                <div class="move-row">
                    <span class="move-num">${idx + 1}.</span>
                    <span class="move-ply">${move.white || ''}</span>
                    <span class="move-ply">${move.black || ''}</span>
                </div>
            `;
        });
        historyContainer.innerHTML = html;
        historyContainer.scrollTop = historyContainer.scrollHeight;
    }

    // Slimme Bot respons (AI tegenstander)
    scheduleBotMove() {
        const delay = Math.floor(Math.random() * 500) + 600; // 600ms - 1100ms voor natuurlijke ervaring
        setTimeout(() => {
            if (this.isGameOver || this.turn !== 'b') return;
            const botMove = this.calculateBestBotMove();
            if (botMove) {
                this.executeMove(botMove.from.r, botMove.from.c, botMove.to.r, botMove.to.c, true);
                this.renderBoard();
            }
        }, delay);
    }

    calculateBestBotMove() {
        // Verzamel alle legale zetten voor Zwart
        const allMoves = [];
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = this.board[r][c];
                if (p && p.startsWith('b')) {
                    const legal = this.getLegalMoves(r, c, this.board, 'b');
                    legal.forEach(m => {
                        allMoves.push({ from: { r, c }, to: m, piece: p });
                    });
                }
            }
        }

        if (allMoves.length === 0) return null;

        // Zoek prioritair naar slaanzetten (pionnen opeten!) of checks
        const captures = allMoves.filter(m => this.board[m.to.r][m.to.c] !== null);
        if (captures.length > 0) {
            // Sla het hoogste waardevolle stuk
            const pieceValues = { p: 1, n: 3, b: 3, r: 5, q: 9, k: 0 };
            captures.sort((a, b) => {
                const targetA = this.board[a.to.r][a.to.c];
                const targetB = this.board[b.to.r][b.to.c];
                const valA = targetA ? (pieceValues[targetA[1]] || 1) : 0;
                const valB = targetB ? (pieceValues[targetB[1]] || 1) : 0;
                return valB - valA;
            });
            return captures[0];
        }

        // Centrumbeheersing (d4, e4, d5, e5)
        const centerMoves = allMoves.filter(m => (m.to.r === 3 || m.to.r === 4) && (m.to.c === 3 || m.to.c === 4));
        if (centerMoves.length > 0 && Math.random() < 0.6) {
            return centerMoves[Math.floor(Math.random() * centerMoves.length)];
        }

        // Anders willekeurige legale zet
        return allMoves[Math.floor(Math.random() * allMoves.length)];
    }

    // Regels en geldige zetten berekenen
    getLegalMoves(r, c, board, color) {
        const piece = board[r][c];
        if (!piece || piece[0] !== color) return [];

        const pseudoMoves = this.getPseudoMoves(r, c, board, true);
        // Filter zetten die de eigen koning in schaak achterlaten
        return pseudoMoves.filter(m => {
            const cloneBoard = board.map(row => [...row]);
            cloneBoard[m.r][m.c] = cloneBoard[r][c];
            cloneBoard[r][c] = null;
            return !this.isKingInCheckOnBoard(color, cloneBoard);
        });
    }

    hasAnyLegalMoves(color) {
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = this.board[r][c];
                if (p && p.startsWith(color)) {
                    const legal = this.getLegalMoves(r, c, this.board, color);
                    if (legal.length > 0) return true;
                }
            }
        }
        return false;
    }

    // Directe aanvalscontrole (geen recursie!)
    isSquareAttacked(targetR, targetC, attackerColor, board = this.board) {
        // 1. Pion aanvallen
        const pawnRow = attackerColor === 'w' ? targetR + 1 : targetR - 1;
        if (pawnRow >= 0 && pawnRow < 8) {
            [-1, 1].forEach(dc => {
                const pc = targetC + dc;
                if (pc >= 0 && pc < 8) {
                    if (board[pawnRow][pc] === attackerColor + 'p') {
                        return true;
                    }
                }
            });
            if ((targetC > 0 && board[pawnRow][targetC - 1] === attackerColor + 'p') ||
                (targetC < 7 && board[pawnRow][targetC + 1] === attackerColor + 'p')) {
                return true;
            }
        }

        // 2. Paard aanvallen
        const knightOffsets = [
            [-2, -1], [-2, 1], [-1, -2], [-1, 2],
            [1, -2], [1, 2], [2, -1], [2, 1]
        ];
        for (const [dr, dc] of knightOffsets) {
            const nr = targetR + dr;
            const nc = targetC + dc;
            if (nr >= 0 && nr < 8 && nc >= 0 && nc < 8) {
                if (board[nr][nc] === attackerColor + 'n') return true;
            }
        }

        // 3. Koning aanvallen (1 veld afstand)
        for (let dr = -1; dr <= 1; dr++) {
            for (let dc = -1; dc <= 1; dc++) {
                if (dr === 0 && dc === 0) continue;
                const nr = targetR + dr;
                const nc = targetC + dc;
                if (nr >= 0 && nr < 8 && nc >= 0 && nc < 8) {
                    if (board[nr][nc] === attackerColor + 'k') return true;
                }
            }
        }

        // 4. Lijnen: Toren & Dame
        const straightDirs = [[-1, 0], [1, 0], [0, -1], [0, 1]];
        for (const [dr, dc] of straightDirs) {
            let nr = targetR + dr;
            let nc = targetC + dc;
            while (nr >= 0 && nr < 8 && nc >= 0 && nc < 8) {
                const piece = board[nr][nc];
                if (piece) {
                    if (piece === attackerColor + 'r' || piece === attackerColor + 'q') {
                        return true;
                    }
                    break;
                }
                nr += dr;
                nc += dc;
            }
        }

        // 5. Diagonalen: Loper & Dame
        const diagDirs = [[-1, -1], [-1, 1], [1, -1], [1, 1]];
        for (const [dr, dc] of diagDirs) {
            let nr = targetR + dr;
            let nc = targetC + dc;
            while (nr >= 0 && nr < 8 && nc >= 0 && nc < 8) {
                const piece = board[nr][nc];
                if (piece) {
                    if (piece === attackerColor + 'b' || piece === attackerColor + 'q') {
                        return true;
                    }
                    break;
                }
                nr += dr;
                nc += dc;
            }
        }

        return false;
    }

    getPseudoMoves(r, c, board, allowCastling = true) {
        const piece = board[r][c];
        if (!piece) return [];
        const color = piece[0];
        const enemyColor = color === 'w' ? 'b' : 'w';
        const type = piece[1];
        const moves = [];

        const isEnemyOrEmpty = (nr, nc) => {
            if (nr < 0 || nr > 7 || nc < 0 || nc > 7) return false;
            const target = board[nr][nc];
            return !target || target[0] !== color;
        };

        const addSliding = (dirs) => {
            dirs.forEach(([dr, dc]) => {
                let nr = r + dr;
                let nc = c + dc;
                while (nr >= 0 && nr < 8 && nc >= 0 && nc < 8) {
                    const target = board[nr][nc];
                    if (!target) {
                        moves.push({ r: nr, c: nc });
                    } else {
                        if (target[0] !== color) {
                            moves.push({ r: nr, c: nc });
                        }
                        break;
                    }
                    nr += dr;
                    nc += dc;
                }
            });
        };

        switch (type) {
            case 'p': { // Pion
                const forward = color === 'w' ? -1 : 1;
                const startRow = color === 'w' ? 6 : 1;
                
                // 1 stap vooruit
                if (r + forward >= 0 && r + forward < 8 && !board[r + forward][c]) {
                    moves.push({ r: r + forward, c });
                    // 2 stappen vooruit vanaf beginpositie
                    if (r === startRow && !board[r + forward * 2][c]) {
                        moves.push({ r: r + forward * 2, c });
                    }
                }
                // Diagonaal slaan
                [-1, 1].forEach(dc => {
                    const nr = r + forward;
                    const nc = c + dc;
                    if (nr >= 0 && nr < 8 && nc >= 0 && nc < 8) {
                        const target = board[nr][nc];
                        if (target && target[0] !== color) {
                            moves.push({ r: nr, c: nc });
                        }
                        // En passant
                        if (this.enPassantSquare && this.enPassantSquare.r === nr && this.enPassantSquare.c === nc) {
                            moves.push({ r: nr, c: nc });
                        }
                    }
                });
                break;
            }
            case 'n': { // Paard
                const knightOffsets = [
                    [-2, -1], [-2, 1], [-1, -2], [-1, 2],
                    [1, -2], [1, 2], [2, -1], [2, 1]
                ];
                knightOffsets.forEach(([dr, dc]) => {
                    const nr = r + dr;
                    const nc = c + dc;
                    if (isEnemyOrEmpty(nr, nc)) {
                        moves.push({ r: nr, c: nc });
                    }
                });
                break;
            }
            case 'b': // Loper
                addSliding([[-1, -1], [-1, 1], [1, -1], [1, 1]]);
                break;
            case 'r': // Toren
                addSliding([[-1, 0], [1, 0], [0, -1], [0, 1]]);
                break;
            case 'q': // Dame
                addSliding([[-1, -1], [-1, 1], [1, -1], [1, 1], [-1, 0], [1, 0], [0, -1], [0, 1]]);
                break;
            case 'k': { // Koning
                for (let dr = -1; dr <= 1; dr++) {
                    for (let dc = -1; dc <= 1; dc++) {
                        if (dr === 0 && dc === 0) continue;
                        if (isEnemyOrEmpty(r + dr, c + dc)) {
                            moves.push({ r: r + dr, c: c + dc });
                        }
                    }
                }
                // Rokade (alleen als niet in schaak en tussenliggende velden veilig)
                if (allowCastling && !this.isSquareAttacked(r, c, enemyColor, board)) {
                    if (color === 'w' && r === 7 && c === 4 && !this.hasMoved.wK) {
                        // Korte rokade
                        if (!this.hasMoved.wR_h && !board[7][5] && !board[7][6] && 
                            !this.isSquareAttacked(7, 5, 'b', board) && !this.isSquareAttacked(7, 6, 'b', board)) {
                            moves.push({ r: 7, c: 6 });
                        }
                        // Lange rokade
                        if (!this.hasMoved.wR_a && !board[7][1] && !board[7][2] && !board[7][3] && 
                            !this.isSquareAttacked(7, 2, 'b', board) && !this.isSquareAttacked(7, 3, 'b', board)) {
                            moves.push({ r: 7, c: 2 });
                        }
                    } else if (color === 'b' && r === 0 && c === 4 && !this.hasMoved.bK) {
                        // Korte rokade
                        if (!this.hasMoved.bR_h && !board[0][5] && !board[0][6] && 
                            !this.isSquareAttacked(0, 5, 'w', board) && !this.isSquareAttacked(0, 6, 'w', board)) {
                            moves.push({ r: 0, c: 6 });
                        }
                        // Lange rokade
                        if (!this.hasMoved.bR_a && !board[0][1] && !board[0][2] && !board[0][3] && 
                            !this.isSquareAttacked(0, 2, 'w', board) && !this.isSquareAttacked(0, 3, 'w', board)) {
                            moves.push({ r: 0, c: 2 });
                        }
                    }
                }
                break;
            }
        }

        return moves;
    }

    findKing(color, board = this.board) {
        const kingPiece = color + 'k';
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                if (board[r][c] === kingPiece) {
                    return { r, c };
                }
            }
        }
        return null;
    }

    isKingInCheck(color) {
        return this.isKingInCheckOnBoard(color, this.board);
    }

    isKingInCheckOnBoard(color, board) {
        const kingPos = this.findKing(color, board);
        if (!kingPos) return false;
        const enemyColor = color === 'w' ? 'b' : 'w';
        return this.isSquareAttacked(kingPos.r, kingPos.c, enemyColor, board);
    }

    // Notatie generator
    generateSAN(fromR, fromC, toR, toC, piece, target) {
        const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        const type = piece[1];
        const pieceLetters = { k: 'K', q: 'D', r: 'T', b: 'L', n: 'P' };
        let san = '';

        if (type === 'k' && Math.abs(toC - fromC) === 2) {
            return toC === 6 ? '0-0' : '0-0-0';
        }

        if (type === 'p') {
            if (target || (this.enPassantSquare && toR === this.enPassantSquare.r && toC === this.enPassantSquare.c)) {
                san = `${files[fromC]}x${files[toC]}${8 - toR}`;
            } else {
                san = `${files[toC]}${8 - toR}`;
            }
        } else {
            const letter = pieceLetters[type] || '';
            const cap = target ? 'x' : '';
            san = `${letter}${cap}${files[toC]}${8 - toR}`;
        }

        return san;
    }

    // Partij beëindigen & opslaan via API
    async endGame(winner, reason, description) {
        this.isGameOver = true;
        clearInterval(this.timerInterval);

        const modal = document.getElementById('game-over-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalDesc = document.getElementById('modal-desc');
        const modalEloDiff = document.getElementById('modal-elo-diff');

        if (modalTitle) modalTitle.textContent = (winner === 'white' ? 'Gewonnen!' : (winner === 'black' ? 'Verloren' : 'Gelijkspel'));
        if (modalDesc) modalDesc.textContent = description;

        // API aanroepen om resultaat en ELO op te slaan
        try {
            const res = await fetch('api.php?action=save_game', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opponent_id: this.opponentId,
                    winner: winner,
                    time_control: this.timeSeconds,
                    reason: reason,
                    moves: this.moveHistory
                })
            });
            const data = await res.json();
            if (data.success && modalEloDiff) {
                const diff = data.elo_change;
                modalEloDiff.textContent = diff >= 0 ? `+${diff} (Nieuwe rating: ${data.new_elo})` : `${diff} (Nieuwe rating: ${data.new_elo})`;
                modalEloDiff.style.color = diff >= 0 ? '#00e620' : '#ff5252';
            }
        } catch (e) {
            if (modalEloDiff) modalEloDiff.textContent = winner === 'white' ? '+8' : (winner === 'black' ? '-8' : '0');
        }

        if (modal) {
            modal.removeAttribute('hidden');
        }
    }

    escapeHTML(str) {
        return str.replace(/[&<>'"]/g, tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag));
    }
}

// Spel initialiseren zodra pagina geladen is
document.addEventListener('DOMContentLoaded', () => {
    window.chessGameInstance = new ChessGame();
});

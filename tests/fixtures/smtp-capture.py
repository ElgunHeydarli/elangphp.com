import socketserver,sys,os
class Handler(socketserver.StreamRequestHandler):
 def handle(self):
  self.wfile.write(b'220 localhost Elang test capture\r\n')
  while True:
   line=self.rfile.readline()
   if not line: return
   cmd=line.decode('ascii','replace').strip().upper()
   if cmd.startswith(('EHLO','HELO')): self.wfile.write(b'250-localhost\r\n250 8BITMIME\r\n')
   elif cmd.startswith(('MAIL FROM','RCPT TO','RSET','NOOP')): self.wfile.write(b'250 OK\r\n')
   elif cmd=='DATA':
    self.wfile.write(b'354 End with a dot\r\n');chunks=[]
    while True:
     row=self.rfile.readline()
     if row==b'.\r\n': break
     if not row: return
     chunks.append(row)
    with open(sys.argv[1],'wb') as f: f.write(b''.join(chunks))
    os.chmod(sys.argv[1],0o600);self.wfile.write(b'250 Captured locally\r\n')
   elif cmd=='QUIT': self.wfile.write(b'221 Bye\r\n');return
   else: self.wfile.write(b'502 Unsupported\r\n')
with socketserver.TCPServer(('127.0.0.1',0),Handler) as server:
 print(server.server_address[1],flush=True);server.handle_request()

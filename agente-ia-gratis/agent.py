import urllib.request
import json

OLLAMA_URL = "http://localhost:11434/api/generate"
MODEL = "llama3.2"

def ask_ollama(prompt):
    data = json.dumps({
        "model": MODEL,
        "prompt": prompt,
        "stream": False,
        "options": {"temperature": 0.7}
    }).encode()
    req = urllib.request.Request(OLLAMA_URL, data=data, headers={"Content-Type": "application/json"})
    resp = urllib.request.urlopen(req)
    return json.loads(resp.read())["response"].strip()

mensaje_entrante = "Hola, intenté iniciar sesión hoy pero dice que mi contraseña expiró. ¿Me pueden ayudar?"

prompt = f"""Eres un Especialista Senior en Soporte al Cliente.
Proporciona respuestas serviciales, amables y muy concisas a las consultas de los usuarios.

Analiza el siguiente mensaje del cliente: '{mensaje_entrante}'
Redacta una respuesta empática, profesional y que resuelva su problema de forma directa."""

print("\n--- EL AGENTE ESTÁ PENSANDO ---")
resultado = ask_ollama(prompt)
print("\n--- RESPUESTA FINAL GENERADA POR EL AGENTE ---")
print(resultado)

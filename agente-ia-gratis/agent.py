from crewai import Agent, Task, Crew, LLM

# 1. Conexión con el cerebro de IA local corriendo en Ollama
local_llm = LLM(
    model="ollama/llama3",
    base_url="http://localhost:11434"
)

# 2. Definición del Agente: Su rol, su meta y su personalidad
agente_soporte = Agent(
    role="Especialista Senior en Soporte al Cliente",
    goal="Proporcionar respuestas serviciales, amables y muy concisas a las consultas de los usuarios.",
    backstory="Eres un agente de soporte empático que trabaja en una empresa tecnológica. Siempre mantienes tus respuestas breves, claras y profesionales.",
    llm=local_llm,
    verbose=True
)

# 3. Simulación de un mensaje entrante (Aquí es donde llegará el texto de tus usuarios)
mensaje_entrante = "Hola, intenté iniciar sesión hoy pero dice que mi contraseña expiró. ¿Me pueden ayudar?"

# 4. Asignación de la tarea que debe realizar el agente
tarea_responder = Task(
    description=f"Analiza el siguiente mensaje del cliente: '{mensaje_entrante}'. Redacta una respuesta empática, profesional y que resuelva su problema de forma directa.",
    expected_output="Una respuesta corta y profesional lista para ser enviada por correo o chat al usuario.",
    agent=agente_soporte
)

# 5. Configuración del equipo de trabajo (Crew) y ejecución
equipo_soporte = Crew(
    agents=[agente_soporte],
    tasks=[tarea_responder]
)

print("\n--- EL AGENTE ESTÁ PENSANDO ---")
resultado = equipo_soporte.kickoff()

print("\n--- RESPUESTA FINAL GENERADA POR EL AGENTE ---")
print(resultado)

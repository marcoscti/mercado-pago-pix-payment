fetch("http://localhost/hardtale/", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    value: 1.0,
    email: "marcosc974@gmail.com"
  }),
})
.then((r) => r.json())
.then((data) => console.log(data))
.catch((err) => console.error("Erro:", err));
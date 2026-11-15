<?php
declare(strict_types=1);

// Navegador mínimo para las pruebas: conserva las cookies de sesión de un usuario y
// envía el token CSRF de la última página visitada, como lo haría un navegador real.
final class ClienteHttp
{
    private string $cookies;
    public string $html = '';
    public int $estado = 0;
    public ?string $redireccion = null;
    public array $cabeceras = [];
    private string $token = '';

    public function __construct(private string $base)
    {
        $this->cookies = tempnam(sys_get_temp_dir(), 'apa-cookies-');
    }

    public function get(string $ruta): self
    {
        return $this->peticion('GET', $ruta);
    }

    /** Envía un formulario con el token CSRF vigente (a menos que se indique otro). */
    public function post(string $ruta, array $datos = [], bool $conToken = true): self
    {
        if ($conToken && !array_key_exists('_token', $datos)) {
            $datos['_token'] = $this->token;
        }
        return $this->peticion('POST', $ruta, http_build_query($datos), ['Content-Type: application/x-www-form-urlencoded']);
    }

    public function postJson(string $ruta, array $datos, bool $conToken = true): array
    {
        $cabeceras = ['Content-Type: application/json', 'Accept: application/json'];
        if ($conToken) {
            $cabeceras[] = 'X-CSRF-Token: ' . $this->token;
        }
        $this->peticion('POST', $ruta, json_encode($datos), $cabeceras);
        return json_decode($this->html, true) ?? [];
    }

    /** Sigue la redirección si la hubo (como hace el navegador tras enviar un formulario). */
    public function seguir(): self
    {
        if ($this->redireccion !== null) {
            $ruta = parse_url($this->redireccion, PHP_URL_PATH) . (parse_url($this->redireccion, PHP_URL_QUERY) ? '?' . parse_url($this->redireccion, PHP_URL_QUERY) : '');
            return $this->get($ruta);
        }
        return $this;
    }

    public function ingresar(string $correo, string $clave): self
    {
        $this->get('/ingresar');
        return $this->post('/ingresar', ['correo' => $correo, 'clave' => $clave]);
    }

    public function salir(): self
    {
        $this->get('/');
        return $this->post('/salir');
    }

    public function cookieSesion(): ?string
    {
        foreach (file($this->cookies) ?: [] as $linea) {
            $partes = explode("\t", trim($linea));
            if (count($partes) === 7 && $partes[5] === 'APASESION') {
                return $partes[6];
            }
        }
        return null;
    }

    public function contiene(string $texto): bool
    {
        return str_contains(html_entity_decode($this->html, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $texto);
    }

    private function peticion(string $metodo, string $ruta, ?string $cuerpo = null, array $cabeceras = []): self
    {
        $c = curl_init($this->base . $ruta);
        curl_setopt_array($c, [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->cookies,
            CURLOPT_COOKIEFILE     => $this->cookies,
            CURLOPT_HTTPHEADER     => $cabeceras,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($cuerpo !== null) {
            curl_setopt($c, CURLOPT_POSTFIELDS, $cuerpo);
        }
        $respuesta = (string) curl_exec($c);
        $tamCabecera = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $this->estado = (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);
        $this->redireccion = curl_getinfo($c, CURLINFO_REDIRECT_URL) ?: null;
        curl_close($c);
        $this->cabeceras = [];
        foreach (explode("\r\n", substr($respuesta, 0, $tamCabecera)) as $linea) {
            if (str_contains($linea, ':')) {
                [$k, $v] = explode(':', $linea, 2);
                $this->cabeceras[strtolower(trim($k))] = trim($v);
            }
        }
        $this->html = substr($respuesta, $tamCabecera);
        if (preg_match('/name="_token" value="([a-f0-9]{64})"/', $this->html, $m)
            || preg_match('/"token":"([a-f0-9]{64})"/', $this->html, $m)) {
            $this->token = $m[1];
        }
        return $this;
    }
}

<footer class="pie-sitio" id="contacto">
    <div class="contenedor">
        <div class="pie-sitio__grid">
            <div>
                <h2>¿Necesitas orientación?</h2>
                <p>Contacto demostrativo. Datos ficticios para fines académicos.</p>
                <a href="{{ route('quienes-somos') }}#preguntas">Consultar preguntas frecuentes <span aria-hidden="true">→</span></a>
            </div>
            <ul class="pie-contacto">
                <li><x-icono nombre="correo" :tamano="17" /><span>contacto@citavital.demo</span></li>
                <li><x-icono nombre="telefono" :tamano="17" /><span>+57 601 000 0000</span></li>
                <li><x-icono nombre="reloj" :tamano="17" /><span>Lunes a viernes · 8:00 a. m. – 5:00 p. m.</span></li>
                <li><x-icono nombre="ubicacion" :tamano="17" /><span>Calle 00 # 00-00 · Bogotá D.C., Colombia</span></li>
            </ul>
        </div>
        <div class="pie-sitio__inferior">
            <p>CitaVital &copy; {{ date('Y') }} · Proyecto académico. No presta servicios médicos reales.</p>
            <a href="{{ route('politica-privacidad') }}">Política de privacidad demostrativa</a>
        </div>
    </div>
</footer>

<pre><code class="language-yaml"># in .symfony/services.yaml
my_project_elasticsearch:
    type: elasticsearch:6.5
    disk: 512
    configuration:
        plugins:
            - analysis-icu</code></pre>
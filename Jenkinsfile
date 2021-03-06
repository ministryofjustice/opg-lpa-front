pipeline {

    agent { label "!master"} //run on slaves only

    environment {
        DOCKER_REGISTRY = 'registry.service.opg.digital'
        IMAGE = 'opguk/lpa-front'
    }

    stages {

        stage('lint') {
            steps {
                echo 'PHP_CodeSniffer PSR-2'
                sh '''
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opg-phpcs-1604 --standard=PSR2 --report=checkstyle --report-file=checkstyle.xml --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true module/Application/src/
                '''
            }
            post {
                always {
                    checkstyle pattern: 'checkstyle.xml'
                }
            }
        }

        stage('unit tests') {
            steps {
                echo 'PHPUnit'
                sh '''
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opg-phpunit-1604 module/Application/tests -c module/Application/tests/phpunit.xml --log-junit unit_results.xml
                '''
            }
            post {
                always {
                    junit 'unit_results.xml'
                }
            }
        }

        stage('unit tests coverage') {
            steps {
                echo 'PHPUnit with coverage'
                sh '''
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opg-phpunit-1604 module/Application/tests -c module/Application/tests/phpunit.xml --coverage-clover module/Application/tests/coverage/clover.xml --coverage-html module/Application/tests/coverage/
                    echo 'Fixing coverage file paths due to running in container'
                    sed -i "s#<file name=\\"/app#<file name=\\"#" module/Application/tests/coverage/clover.xml
                '''
                step([
                    $class: 'CloverPublisher',
                    cloverReportDir: 'module/Application/tests/coverage',
                    cloverReportFileName: 'clover.xml'
                ])
            }
        }

        stage('build') {
            steps {
                sh '''
                    docker-compose down
                    docker-compose build
                '''
            }
        }

        stage('functional tests') {
            steps {
                sh '''
                    docker-compose run --rm --user `id -u` tests
                    docker-compose down
                '''
            }
            post {
                always {
                    junit 'module/Application/tests/functional/functional_results.xml'
                }
            }
        }

        stage('create the tag') {
            steps {
                script {
                    if (env.BRANCH_NAME != "master") {
                        env.STAGEARG = "--stage ci"
                    } else {
                        // this can change to `-dev` tags we we switch over.
                        env.STAGEARG = "--stage master"
                    }
                }
                script {
                    sh '''
                        virtualenv venv
                        . venv/bin/activate
                        pip install git+https://github.com/ministryofjustice/semvertag.git@1.1.0
                        git fetch --tags
                        semvertag bump patch $STAGEARG >> semvertag.txt
                        NEWTAG=$(cat semvertag.txt); semvertag tag ${NEWTAG}
                    '''
                    env.NEWTAG = readFile('semvertag.txt').trim()
                    currentBuild.description = "${IMAGE}:${NEWTAG}"
                }
                echo "Storing ${env.NEWTAG}"
                archiveArtifacts artifacts: 'semvertag.txt'
            }
        }

        stage('build image') {
            steps {
                sh '''
                  docker build . -t ${DOCKER_REGISTRY}/${IMAGE}:${NEWTAG}
                '''
            }
        }

        stage('push image') {
            steps {
                sh '''
                  docker push ${DOCKER_REGISTRY}/${IMAGE}:${NEWTAG}
                '''
            }
        }

        stage('trigger downstream build') {
            when {
                branch 'master'
            }
            steps {
                build job: '/lpa/opg-lpa-docker/master', propagate: false, wait: false
            }
        }
    }

    post {
        // Always cleanup docker containers, especially for aborted jobs.
        always {
            sh '''
              docker-compose down --remove-orphans
            '''
        }
    }

}

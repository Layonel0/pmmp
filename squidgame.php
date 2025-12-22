
namespace squidgame;

class squidgame extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener
{
	public $servidor = null;
	public $s = "§l§eSquidGame§r§8: §f";
	//publics juego luz
	public $luzverdejugadores = [];
	public $luzrojajugadores = [];
	public $cancelarluz = false;
	public $ganojugadorluz = [];
	public $tiempomoverse = 0; // cuando se mueve el jugador
	public $empezoprimerjuegoluz = false;
	public $pisolineaverde = [];
	//juego 2 camas
	public $perdiojugadorluz = [];
	public $equipoazul = [];//el grupo incial que se crea
	public $equipoazulreal = [];//cada miembro del grupo
	public $equiporojo = [];//el grupo incial que se crea
	public $equiporojoreal = [];//cada miembro del grupo
	public $cruzelineascamas = false;
	//juego 3 puente
	public $jugadorespuente = []; //los jugadores que ingresan al puente
	public $lineaverdepuente = false; //para que no pase la linea si el evento aun no a ocurrido
	public $ganadorpuente = []; //ganadores del evento puente
	public $pisolineaverdepuente = [];
	public $agachado = [];
	public $cancelarpuente = false;
	public $perdiojugadorpuente = [];

	public function onEnable()
	{
		$this->servidor = \pocketmine\Server::getInstance();
		$this->servidor->getPluginManager()->registerEvents($this,$this); 
	    @mkdir($this->getDataFolder());
	    $this->flechasxyz = new \pocketmine\utils\Config($this->getDataFolder() . "flechasxyz.yml", \pocketmine\utils\Config::YAML);
	    \pocketmine\entity\Entity::registerEntity(\squidgame\flecha::class);
	}

	//COMANDOS BASICOS DEL JUEGO

	public function onCommand(\pocketmine\command\CommandSender $jugador,\pocketmine\command\Command $comando,$nose,array $lineas)
	{
		if($comando->getName() === "lgame")
		{
			if(true == true)
			{
				if(count($lineas) < 1)
				{
					$jugador->sendMessage($this->s . "/lgame luz+/-");
					return false;
				}
				if($lineas[0] === "luz+")
				{
					$this->servidor->broadcastMessage($this->s . "§fEl juego LUZ §aVERDE §fLUZ §cROJA §fha empezado.");
					foreach($this->servidor->getOnlinePlayers() as $jugadores)
					{
						if($jugadores->getLevel()->getName() === "01") //luz verde luz roja
						{
							$this->EsperarTiempoLuz($jugadores,true); //true luz verde false luz roja
							$this->cancelarluz = false;
						}
					}
					return true;
				}elseif($lineas[0] === "luz-")
			    {
					foreach($this->servidor->getOnlinePlayers() as $jugadores)
					{
						if($jugadores->getLevel()->getName() === "01") //luz verde luz roja
						{
				            if(isset($this->ganojugadorluz[$jugadores->getName()]))
				            {
				            	unset($this->ganojugadorluz[$jugadores->getName()]);
				            }elseif(isset($this->luzverdejugadores[$jugadores->getName()]))
				            {
				            	unset($this->luzverdejugadores[$jugadores->getName()]);
				            }elseif(isset($this->luzrojajugadores[$jugadores->getName()]))
				            {
				            	unset($this->luzrojajugadores[$jugadores->getName()]);
				            }elseif(isset($this->perdiojugadorluz[$jugadores->getName()]))
				            {
				            	unset($this->perdiojugadorluz[$jugadores->getName()]);
				            }
				        }
				    }
					$this->servidor->broadcastMessage($this->s . "§fEl juego LUZ §aVERDE §fLUZ §cROJA §fha sido pausado.");

					$this->cancelarluz = true;
					$this->empezoprimerjuegoluz = false;
					return false;
				}elseif($lineas[0] === "camas+")
				{
					foreach($this->servidor->getOnlinePlayers() as $jugadores)
					{
						if($jugadores->getLevel()->getName() === "07" and $jugadores->getGamemode() !== 3)
						{
							$jugadores->getInventory()->setItemInHand(\pocketmine\item\Item::get(268));//espada de madera
							$jugadores->getInventory()->setChestPlate(\pocketmine\item\Item::get(303));//pechera de maya
							$jugadores->setHealth(20);
							$jugadores->setFood(20);
						}
					}
					$this->servidor->broadcastMessage($this->s . "§cLas luces se apagaran en unos minutos... §4:>");
					$this->TiempoDormir();

				}elseif($lineas[0] === "camas-") //encender las luces
				{
					foreach($this->servidor->getOnlinePlayers() as $j)
					{
						if(isset($this->equipoazulreal[$j->getName()]))
						{
							$j->teleport($this->servidor->getLevelByName("05")->getSafeSpawn());
				            $j->teleport(new \pocketmine\math\Vector3(170,6,119));
				        }elseif(isset($this->equiporojoreal[$j->getName()]))
				        {
				        	$j->teleport($this->servidor->getLevelByName("05")->getSafeSpawn());
				            $j->teleport(new \pocketmine\math\Vector3(136,6,118));
				        }
				        $j->getInventory()->clearAll();
				        $j->removeAllEffects();
						$j->setHealth(20);
						$j->setFood(20);
						$j->setNametagVisible(false);
				    }
					$this->cruzelineascamas = false;
					$this->servidor->broadcastMessage($this->s . "§aLas luces han sido encendidas.");
					$this->mensajedelay("§fSe asesinaron varios jugadores mientras se apagaron las luces..",5);
					$total = 0;
					foreach ($this->servidor->getOnlinePlayers() as $j)
					{
						if(!$j->isOp() and $j->getGamemode() !== 3)
						{
							$total++;
						}
					}
					$this->mensajedelay("§fQuedo un total de §7[§e{$total}§7] §fJugadores...",7);
				}elseif($lineas[0] === "camas/") //resetear los equipos
				{
					$this->cruzelineascamas = false;
                    $ganadores = array_keys($this->ganojugadorluz);
                    shuffle($ganadores);
                    $totalJugadores = count($ganadores);
                    $mitad = ceil($totalJugadores / 2);
					$this->equipoazul = array_slice($ganadores, 0, $mitad);
					$this->equiporojo = array_slice($ganadores, $mitad);
					//<-
					foreach($ganadores as $nombre){
					    $jugador = $this->servidor->getPlayerExact($nombre);
					    if($jugador !== null && $jugador->isOnline()){
					        if(in_array($nombre, $this->equipoazul)){
					            $jugador->teleport(new \pocketmine\math\Vector3(170,6,119));
					            $jugador->sendTip($this->s . "§fHas sido asignado al equipo §8( §1O §8)");
					        } else { // rojo
					            $jugador->teleport(new \pocketmine\math\Vector3(136,6,118));
					            $jugador->sendTip($this->s . "§fHas sido asignado al equipo §8( §cX §8)");
					        }
					    }
					}
                }elseif($lineas[0] === "puente+")
                {
                	foreach($this->servidor->getOnlinePlayers() as $jugadores)
                	{
                		if(isset($this->equipoazulreal[$jugadores->getName()]))
                		{
                			$jugadores->teleport($this->servidor->getLevelByName("05")->getSafeSpawn());
                			$this->jugadorespuente[$jugadores->getName()] = true;
                			$this->EsperarTiempoPuente($jugadores);
                		}elseif($this->equiporojoreal[$jugadores->getName()])
                		{
                			$this->jugadorespuente[$jugadores->getName()] = true;
                			$jugadores->teleport($this->servidor->getLevelByName("05")->getSafeSpawn());
                			$this->EsperarTiempoPuente($jugadores);
                		}
                	}
                	$this->cancelarpuente = false;
                	$this->lineaverdepuente = false;
                	$this->mensajedelay("§fBienvenidos al segundo §djuego.. §fespero y tengan la misma suerte §c:>.",1);
                	$this->mensajedelay("§fEste juego consiste en que te tienes que §aagachar §fsiempre que la barra de carga este §eamarilla",4);
                }elseif($lineas[0] === "puente-")
                {
                	foreach($this->servidor->getOnlinePlayers() as $jugadores)
                	{
                		if(isset($this->equipoazulreal[$jugadores->getName()]))
                		{
                			$jugadores->teleport($this->servidor->getLevelByName("05"));
                			$jugadores->teleport(\pocketmine\math\Vector3(137,15,123));
                			$jugadores->setGamemode(0);
                			$this->jugadorespuente[$jugadores->getName()] = true;
                			$jugadores->setNametagVisible(true);
                		}elseif($this->equiporojoreal[$jugadores->getName()])
                		{
                			$jugadores->teleport($this->servidor->getLevelByName("05"));
                			$jugadores->teleport(\pocketmine\math\Vector3(137,15,123));
                			$jugadores->setGamemode(0);
                			$jugadores->setNametagVisible(true);
                			$this->jugadorespuente[$jugadores->getName()] = true;
                		}
                		if(isset($this->ganadorpuente[$this->jugador->getName()]))
                		{
                			unset($this->ganadorpuente[$this->jugador->getName()]);
                		}
                	}
                	$this->cancelarpuente = true;
                	$this->lineaverdepuente = false;
                }
			}
		}elseif($comando->getName() === "lt")
		{
			if($jugador->getGamemode() !== 3 and !$jugador->isOp())
			{
				$jugador->sendMessage($this->s . "§fDebes estar descalificado para poder especteal a los otros jugadores.");
				return false;
			}
			if(count($lineas) < 1)
			{
				$jugador->sendMessage($this->s . "§f/lt <nombre del jugador> | ejemplo§r§8: §f/lt layonel");
				return false;
			}
			if(!is_null($this->servidor->getPlayer($lineas[0])))
			{
				$jugador->teleport($this->servidor->getPlayer($lineas[0])->getPosition());
				$jugador->sendMessage($this->s . "Especteando a el jugador§8: §a". $this->servidor->getPlayer($lineas[0])->getName());
			}
		}
	}

    public function agachado(\pocketmine\event\player\PlayerToggleSneakEvent $l)
    {
        $jugador = $l->getPlayer();      
        if ($jugador->getLevel()->getName() === "05")
        {
            if ($l->isSneaking())
            {
                $this->agachado[$jugador->getName()] = true;
            } else {
                if (isset($this->agachado[$jugador->getName()]))
                {
                    unset($this->agachado[$jugador->getName()]);
                }
            }
        }
    }

	public function Muere(\pocketmine\event\player\PlayerDeathEvent $l)
	{
		$jugador = $l->getPlayer();
		/*if(isset($this->equipoazulreal[$jugador->getName()]))
		{
			unset($this->equipoazulreal[$jugador->getName()]);
		}
		if(isset($this->equiporojoreal[$jugador->getName()]))
		{
			unset($this->equipoazulreal[$jugador->getName()]);
		}*/
		$jugador->sendMessage($this->s . "§fHas sido eliminado del evento puedes especteal a los otros jugadores con el comando /lt <nombre del jugador>");
		$jugador->setGamemode(3);
	}
	public function Entra(\pocketmine\event\player\PlayerJoinEvent $l)
	{
		$jugador = $l->getPlayer();
	}

	public function Sonido($jugador, $id)
	{
	    switch($id)
	    {
	        case 1:
	            $this->sonido = new \pocketmine\level\sound\ClickSound($jugador->getPosition());
	            break;
	        case 2:
	            $this->sonido = new \pocketmine\level\sound\EndermanTeleportSound($jugador->getPosition());
	            break;
	        case 3:
	            $this->sonido = new \pocketmine\level\sound\BlazeShootSound($jugador->getPosition());
	            break;
	        default:
	            return;
	    }

	    $jugador->getLevel()->addSound($this->sonido);
	}

	public function mensajedelay($mensaje,$tiempo)
	{
	    $tarea = new class($this,$mensaje,$tiempo) extends \pocketmine\scheduler\Task {
	        private $pl;
	        private $tareaId;
	        private $mensaje;
	        private $tiempo;

	        public function __construct(squidgame $pl,$mensaje,$tiempo) {
	            $this->pl = $pl;
	            $this->mensaje = $mensaje;
	            $this->tiempo = $tiempo;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	        	$this->pl->servidor->broadcastMessage($this->pl->s . $this->mensaje);
	        }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleDelayedTask($tarea, 20 * $tiempo); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}

	public function EsperarTiempoLuz($jugador) {
	    $tarea = new class($this, $jugador) extends \pocketmine\scheduler\Task {
	        private $pl;
	        private $jugador;
	        private $tiempo = 0;
	        private $tareaId;

	        public function __construct(squidgame $pl, $jugador) {
	            $this->pl = $pl;
	            $this->jugador = $jugador;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	            $this->tiempo++;
	            if(isset($this->pl->ganojugadorluz[$this->jugador->getName()]))
	            {
	            	unset($this->pl->ganojugadorluz[$this->jugador->getName()]);
	            }elseif(isset($this->pl->luzverdejugadores[$this->jugador->getName()]))
	            {
	            	unset($this->pl->luzverdejugadores[$this->jugador->getName()]);
	            }elseif(isset($this->pl->luzrojajugadores[$this->jugador->getName()]))
	            {
	            	unset($this->pl->luzrojajugadores[$this->jugador->getName()]);
	            }

	            if ($this->pl->cancelarluz == true) {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                return;
	            }
	            if ($this->tiempo <= 10) {
	                $color = ($this->tiempo <= 5) ? 'a' : (($this->tiempo <= 7) ? 'e' : 'c');
	                $this->jugador->sendTip("§fEl juego empieza en: §7[§$color" . $this->tiempo . "§f/§{$color}10§7]");
	                $this->pl->sonido($this->jugador,1);
	            } elseif ($this->tiempo === 11 || $this->tiempo === 12) {
	                $this->jugador->sendTip("§fSolo muévete si la luz está en §aVERDE");
	                $this->pl->empezoprimerjuegoluz = true;
	            } elseif ($this->tiempo === 13) {
	                // Fin del countdown, iniciar la tarea de luz
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                $this->luzverdejugadores[$this->jugador->getName()] = true;
	                $this->pl->EsperarLuz($this->jugador, true); // Inicia con luz verde
	                $this->pl->TiempoJuegoLuz();
	            }
	        }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleRepeatingTask($tarea, 20); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}

	public function EsperarTiempoPuente($jugador) {
	    $tarea = new class($this, $jugador) extends \pocketmine\scheduler\Task {
	        private $pl;
	        private $jugador;
	        private $tiempo = 0;
	        private $tareaId;

	        public function __construct(squidgame $pl, $jugador) {
	            $this->pl = $pl;
	            $this->jugador = $jugador;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	            $this->tiempo++;

	            if ($this->pl->cancelarluz == true) {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                return;
	            }
	            if (isset($this->ganadorpuente[$this->jugador->getName()]))
	            {
	            	unset($this->ganadorpuente[$this->jugador->getName()]);
	            }
	            if(isset($this->perdiojugadorpuente[$this->jugador->getName()]))
	            {
	            	unset($this->perdiojugadorpuente[$this->jugador->getName()]);
	            }
	            if ($this->tiempo <= 10) {
	                $color = ($this->tiempo <= 5) ? 'a' : (($this->tiempo <= 7) ? 'e' : 'c');
	                $this->jugador->sendTip("§fEl juego empieza en: §7[§$color" . $this->tiempo . "§f/§{$color}10§7]");
	                $this->pl->sonido($this->jugador,1);
	            } elseif ($this->tiempo === 11 || $this->tiempo === 12) {
	                $this->jugador->sendTip("§fSolo agachate y avanza si la barra esta §eAmarilla");
	            } elseif ($this->tiempo === 13) {
	                // Fin del countdown, iniciar la tarea de luz
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                $this->pl->EsperarPuente($this->jugador, true); // Inicia con luz verde
	                $this->pl->TiempoJuegoPuente();
	                $this->jugador->addEffect(\pocketmine\entity\Effect::getEffect(\pocketmine\entity\Effect::BLINDNESS)->setDuration(PHP_INT_MAX)->setAmplifier(3)->setVisible(false));
	                $this->jugador->setNametagVisible(false);
	                $this->pl->lineaverdepuente = true;
	            }
	        }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleRepeatingTask($tarea, 20); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}

	public function TiempoJuegoLuz()
	{
	    $tarea = new class($this) extends \pocketmine\scheduler\Task
	    {
	        private $pl;
	        private $tiempo = 0;
	        private $tareaId;
	        private $duracionTotal = 120; // Duración total del juego en segundos (2 minutos)

	        public function __construct(squidgame $pl) {
	            $this->pl = $pl;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	            $this->tiempo++;
	            if($this->pl->cancelarluz === true)
	            {
	            	$this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            	$this->tiempo = 0;
	            }
	            $tiempoRestante = $this->duracionTotal - $this->tiempo;

	            // Calcular minutos y segundos restantes
	            $minutos = floor($tiempoRestante / 60);
	            $segundos = $tiempoRestante % 60;
	            $formatoTiempo = sprintf("%d:%02d", $minutos, $segundos); // Formato MM:SS
	            foreach ($this->pl->servidor->getOnlinePlayers() as $jugadores)
	            {
	            	$jugadores->sendPopup("§eTiempo restante§8: §a" . $formatoTiempo);
	            }
	            // Si el tiempo llega a 0, terminar el juego
	            if ($tiempoRestante <= 0) {
	            	foreach($this->pl->servidor->getOnlinePlayers() as $jugadores)
	            	{
	            		if(!isset($this->pl->ganojugadorluz[$jugadores->getName()]))
	            		{
	            			$jugadores->setGamemode(3);
	            			$jugadores->sendMessage($this->pl->s . "§fHas sido eliminado del evento puedes especteal a los otros jugadores con el comando /lt <nombre del jugador>");
	            			$this->pl->perdiojugadorluz[$jugadores->getName()] = true;
				            if(isset($this->pl->luzverdejugadores[$jugadores->getName()]))
				            {
				            	unset($this->pl->luzverdejugadores[$jugadores->getName()]);
				            	unset($this->pl->ganojugadorluz[$jugadores->getName()]);
				            }elseif(isset($this->pl->luzrojajugadores[$jugadores->getName()]))
				            {
				            	unset($this->pl->luzrojajugadores[$jugadores->getName()]);
				                unset($this->pl->ganojugadorluz[$jugadores->getName()]);
				            }
	            		}else{
	                        $jugadores->teleport($this->pl->servidor->getLevelByName("07")->getSafeSpawn());
	                    }
	                    $jugadores->sendTip($this->pl->s . "§aFelicidades §fa todos los jugadores que pasaron el primer nivel. §d¡Juego terminado!.");
	                }
                    //$jugadores->setGamemode(0);
                    //->Repartir los jugadores en 2 equipos xD
                    $ganadores = array_keys($this->pl->ganojugadorluz);
                    shuffle($ganadores);
                    $totalJugadores = count($ganadores);
                    $mitad = ceil($totalJugadores / 2);
					$this->pl->equipoazul = array_slice($ganadores, 0, $mitad);
					$this->pl->equiporojo = array_slice($ganadores, $mitad);
					//<-
					foreach($ganadores as $nombre){
					    $jugador = $this->pl->servidor->getPlayerExact($nombre);
					    if($jugador !== null && $jugador->isOnline()){
					        if(in_array($nombre, $this->pl->equipoazul)){
					            $jugador->teleport(new \pocketmine\math\Vector3(170,6,119));
					            $jugador->sendTip($this->pl->s . "§fHas sido asignado al equipo §8( §1O §8)");
					            $this->pl->equipoazulreal[$jugador->getName()] = true;
					        } else { // rojo
					            $jugador->teleport(new \pocketmine\math\Vector3(136,6,118));
					            $jugador->sendTip($this->pl->s . "§fHas sido asignado al equipo §8( §cX §8)");
					            $this->pl->equiporojoreal[$jugador->getName()] = true;
					        }
					    }
					}
	                // Cancelar la tarea para detener el temporizador
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            }
	        }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleRepeatingTask($tarea, 20); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}

	public function TiempoJuegoPuente()
	{
	    $tarea = new class($this) extends \pocketmine\scheduler\Task
	    {
	        private $pl;
	        private $tiempo = 0;
	        private $tareaId;
	        private $duracionTotal = 120; // Duración total del juego en segundos (2 minutos)

	        public function __construct(squidgame $pl) {
	            $this->pl = $pl;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	            $this->tiempo++;
	            if($this->pl->cancelarpuente === true)
	            {
	            	$this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            	$this->tiempo = 0;
	            }
	            $tiempoRestante = $this->duracionTotal - $this->tiempo;

	            // Calcular minutos y segundos restantes
	            $minutos = floor($tiempoRestante / 60);
	            $segundos = $tiempoRestante % 60;
	            $formatoTiempo = sprintf("%d:%02d", $minutos, $segundos); // Formato MM:SS
	            foreach ($this->pl->servidor->getOnlinePlayers() as $jugadores)
	            {
	            	if($jugadores->getLevel()->getName() === "05")
	            	{
	            		$jugadores->sendPopup("§eTiempo restante§8: §a" . $formatoTiempo);
	            	}
	            }
	            // Si el tiempo llega a 0, terminar el juego
	            if ($tiempoRestante <= 0) {
	            	foreach($this->pl->servidor->getOnlinePlayers() as $jugadores)
	            	{
	            		if(!isset($this->pl->ganadorpuente[$jugadores->getName()]))
	            		{
	            			$jugadores->setGamemode(3);
	            			$jugador->sendMessage($this->s . "§fHas sido eliminado del evento puedes especteal a los otros jugadores con el comando /lt <nombre del jugador>");
	            			$jugadores->removeAllEffects();
	            			$jugadores->setNametagVisible(true);
	            			$this->pl->perdiojugadorpuente[$jugadores->getName()] = true;
	            		}else{
	            			if(isset($this->pl->equipoazulreal[$jugadores->getName()]))
	            			{
	            				$jugadores->teleport($this->pl->servidor->getLevelByName("07")->getSafeSpawn());
					            $jugadores->teleport(new \pocketmine\math\Vector3(170,6,119));
					            $jugadores->removeAllEffects();
					            $jugadores->setNametagVisible(true);
	                        }elseif(isset($this->pl->equiporojoreal[$jugadores->getName()]))
	                        {
	                        	$jugadores->teleport($this->pl->servidor->getLevelByName("07")->getSafeSpawn());
	                        	$jugadores->teleport(new \pocketmine\math\Vector3(136,6,118));
	                        	$jugadores->removeAllEffects();
	                        	$jugadores->setNametagVisible(true);
	                        }
	                        unset($this->pl->ganadorpuente[$jugadores->getName()]);
	                    }
	                    $jugadores->sendTip($this->pl->s . "§aFelicidades §fa todos los jugadores que pasaron el segundo nivel. §d¡Juego terminado!.");
	                }
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            }
                
            }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleRepeatingTask($tarea, 20); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}

	public function TiempoDormir()
	{
	    $tarea = new class($this) extends \pocketmine\scheduler\Task
	    {
	        private $pl;
	        private $tiempo = 0;
	        private $tareaId;
	        private $duracionTotal = 10; // Duración total del juego en segundos (2 minutos)

	        public function __construct(squidgame $pl) {
	            $this->pl = $pl;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	            $this->tiempo++;
	            $tiempoRestante = $this->duracionTotal - $this->tiempo;

	            // Calcular minutos y segundos restantes
	            $minutos = floor($tiempoRestante / 60);
	            $segundos = $tiempoRestante % 60;
	            $formatoTiempo = sprintf("%d:%02d", $minutos, $segundos); // Formato MM:SS
	            foreach ($this->pl->servidor->getOnlinePlayers() as $jugadores)
	            {
	            	$jugadores->sendPopup("§eTiempo restante§8: §a" . $formatoTiempo);
	            }
	            // Si el tiempo llega a 0, terminar el juego
	            if ($tiempoRestante <= 0)
	            {
	            	foreach($this->pl->servidor->getOnlinePlayers() as $jugadores)
	            	{
	            		if($jugadores->getLevel()->getName() === "07")
	            		{
	            			$this->pl->cruzelineascamas = true;
				            $jugadores->addEffect(\pocketmine\entity\Effect::getEffect(\pocketmine\entity\Effect::BLINDNESS)->setDuration(PHP_INT_MAX)->setAmplifier(1)->setVisible(false));
				            $jugadores->setNametagVisible(false);
				            if(isset($this->pl->equipoazulreal[$jugadores->getName()]))
				            {
				            	$jugadores->sendMessage($this->pl->s . "§cAcaba con el equipo contrario los §8( §cX §8)");
				            }elseif(isset($this->pl->equiporojoreal[$jugadores->getName()]))
				            {
				            	$jugadores->sendMessage($this->pl->s . "§cAcaba con el equipo contrario los §8( §1X §8)");
				            }
				        }
				    }
				    $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            }
	        }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleRepeatingTask($tarea, 20); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}
	
	public function EsperarLuz($jugador, $valor) {
	    $tarea = new class($this, $jugador, $valor) extends \pocketmine\scheduler\Task {
	        private $pl;
	        private $jugador;
	        private $tiempo = 0;
	        private $tareaId;
	        private $valor; // true = verde, false = roja

	        public function __construct(squidgame $pl, $jugador, $valor) {
	            $this->pl = $pl;
	            $this->jugador = $jugador;
	            $this->valor = $valor;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	            $this->tiempo++;
	            if ($this->pl->cancelarluz == true) {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                return;
	            }
	            if(isset($this->pl->ganojugadorluz[$this->jugador->getName()]))
	            {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            }
	            if(isset($this->pl->perdiojugadorluz[$this->jugador->getName()]))
	            {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                unset($this->luzverdejugadores[$this->jugador->getName()]);
	                unset($this->luzrojajugadores[$this->jugador->getName()]);
	            }

	            $patrones = [
	                1 => ['verde' => '§a▌▌▌§7▌', 'roja' => '§c▌▌▌§7▌'],
	                2 => ['verde' => '§a▌▌§7▌▌', 'roja' => '§c▌▌§7▌▌'],
	                3 => ['verde' => '§a▌§7▌▌▌', 'roja' => '§c▌§7▌▌▌'],
	                4 => '§7▌▌▌▌'
	            ];
	            if($this->tiempo >= 1 && $this->tiempo <= 3)
	            {
	                $tipo = $this->valor ? 'verde' : 'roja';
	                $this->jugador->sendTip($patrones[$this->tiempo][$tipo] . str_repeat(" ", 5));
	                $this->pl->sonido($this->jugador,1);
	                if ($this->valor) {
	                    $this->pl->luzverdejugadores[$this->jugador->getName()] = true;
	                } else {
	                    $this->pl->luzrojajugadores[$this->jugador->getName()] = true;
	                }
	            }elseif($this->tiempo == 4)
	            {
	            	$this->jugador->sendTip($patrones[4] . str_repeat(" ", 5));
	            	$this->pl->sonido($this->jugador,1);
	            }elseif($this->tiempo == 5)
	            {
	                if ($this->valor) {
	                    unset($this->pl->luzverdejugadores[$this->jugador->getName()]);
	                } else {
	                    unset($this->pl->luzrojajugadores[$this->jugador->getName()]);
	                }
	                if ($this->valor) {
	                    $this->jugador->sendTip("§c!No te muevas o seras eliminado!");
	                } else {
	                    $this->jugador->sendTip("§a!Muevete hasta que la luz verde cambie!");
	                }
	            }elseif($this->tiempo == 6)
	            {
	                // Cambiar a la otra luz y reiniciar
	                $nuevoValor = !$this->valor;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                $this->pl->EsperarLuz($this->jugador, $nuevoValor);
	            }
	        }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleRepeatingTask($tarea, 20); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}

	public function EsperarPuente($jugador, $valor) {
	    $tarea = new class($this, $jugador, $valor) extends \pocketmine\scheduler\Task {
	        private $pl;
	        private $jugador;
	        private $tiempo = 0;
	        private $tareaId;
	        private $valor; // true = verde, false = roja

	        public function __construct(squidgame $pl, $jugador, $valor) {
	            $this->pl = $pl;
	            $this->jugador = $jugador;
	            $this->valor = $valor;
	        }

	        public function setTaskId(int $tareaId) {
	            $this->tareaId = $tareaId;
	        }

	        public function onRun($tick) {
	            $this->tiempo++;
	            if ($this->pl->cancelarpuente == true) {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                return;
	            }
	            if (isset($this->pl->perdiojugadorpuente[$this->jugador->getName()]))
	            {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            }
	            if(isset($this->pl->ganadorpuente[$this->jugador->getName()]))
	            {
	                $this->tiempo = 0;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	            }


	            $patrones = [
	                1 => ['amarillo' => '§e▌▌▌§7▌', 'roja' => '§c▌▌▌§7▌'],
	                2 => ['amarillo' => '§e▌▌§7▌▌', 'roja' => '§c▌▌§7▌▌'],
	                3 => ['amarillo' => '§e▌§7▌▌▌', 'roja' => '§c▌§7▌▌▌'],
	                4 => '§7▌▌▌▌'
	            ];
	            if($this->tiempo >= 1 && $this->tiempo <= 3)
	            {
	                $tipo = $this->valor ? 'amarillo' : 'roja';
	                $this->jugador->sendTip($patrones[$this->tiempo][$tipo] . str_repeat(" ", 5));
	                $this->pl->sonido($this->jugador,1);
	                if (!$this->valor) {
	                	if(!isset($this->pl->agachado[$this->jugador->getName()]))
	                	{
	                		$this->jugador->teleport($this->pl->servidor->getLevelByName("05")->getSafeSpawn());
	                		$this->jugador->sendMessage($this->pl->s. "§fDebes estar agachado cuando la barra este §eAmarilla §frapido el tiempo se acaba.");
	                	}
	                }
	            }elseif($this->tiempo == 4)
	            {
	            	$this->jugador->sendTip($patrones[4] . str_repeat(" ", 5));
	            	$this->pl->sonido($this->jugador,1);
	            }elseif($this->tiempo == 5)
	            {
	                /*if ($this->valor) {
	                    unset($this->pl->luzverdejugadores[$this->jugador->getName()]);
	                } else {
	                    unset($this->pl->luzrojajugadores[$this->jugador->getName()]);
	                }*/
	                if ($this->valor) {
	                    $this->jugador->sendTip("§c!Agachate o seras eliminado!");
	                } else {
	                    $this->jugador->sendTip("§a!Muevete rapido!");
	                }
	            }elseif($this->tiempo == 6)
	            {
	                // Cambiar a la otra luz y reiniciar
	                $nuevoValor = !$this->valor;
	                $this->pl->servidor->getScheduler()->cancelTask($this->tareaId);
	                $this->pl->EsperarPuente($this->jugador, $nuevoValor);
	            }
	        }
	    };
	    $tareaAsignada = $this->servidor->getScheduler()->scheduleRepeatingTask($tarea, 20); // Cada 20 ticks (1 segundo)
	    $tarea->setTaskId($tareaAsignada->getTaskId());
	}

	/*public  function Entro(\pocketmine\event\player\PlayerJoinEvent $l)
	{
		$jugador = $l->getPlayer();
	}*/
	public function Brinco(\pocketmine\event\server\DataPacketReceiveEvent $l)
	{
		$packet = $l->getPacket();
		$jugador = $l->getPlayer();
		if($packet instanceof \pocketmine\network\protocol\PlayerActionPacket)
		{
			if($jugador->getLevel()->getName() === "05" and $this->lineaverdepuente === true)
			{
				if($packet->action === 8 and !isset($this->ganadorpuente[$jugador->getName()])) //brincar
				{
					$motion = $jugador->getMotion();
					$motion->y = 0;
					$jugador->setMotion($motion);
				}
				if($packet->action === 9 and !isset($this->ganadorpuente[$jugador->getName()])) //correr
				{
					$jugador->setFood(4);
				}
			}
		}
	}

	public function rayo(\pocketmine\Player $jugador,$valor)
	{//uno que lo vean todos y otro que lo veas tu
		$pk = new \pocketmine\network\protocol\AddEntityPacket();
		$pk->eid = hexdec(substr(uniqid(), -8)); //esto genera un id o numero unico para el rayo :v pa que no de crash
		$pk->type = 93;
		$pk->x = $jugador->x;
		$pk->y = $jugador->y;
		$pk->z = $jugador->z;
		$pk->yaw = $jugador->getYaw();
		$pk->pitch = $jugador->getPitch();
		if($valor === true)
		{
			foreach ($this->servidor->getOnlinePlayers() as $jugadores)
			{
				if($jugadores->getName() !== $jugador->getName())
				{
					$jugadores->dataPacket($pk);
				}
			}
		}else{
			$jugador->dataPacket($pk);
		}
	}

	public function Moverse(\pocketmine\event\player\PlayerMoveEvent $l)
	{
		$jugador = $l->getPlayer();
		if($jugador->getLevel()->getName() === "01") //luz verde luz roja
		{
			$lana = $jugador->getLevel()->getBlock($jugador->floor()); // Y-1 abajo
			$lana1 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 1, 0)); // Y-1 abajo
			$lana2 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 2, 0)); // Y-2 abajo
			$lana3 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 3, 0)); // Y-3 abajo
			$lanaroja = (($lana->getId() === 35 && $lana->getDamage() === 14) || ($lana1->getId() === 35 && $lana1->getDamage() === 14) || ($lana2->getId() === 35 && $lana2->getDamage() === 14) || ($lana3->getId() === 35 && $lana3->getDamage() === 14));
			if($lanaroja)
			{
				if(!isset($this->ganojugadorluz[$jugador->getName()]))
				{
					$this->tiempomoverse = time();
					$jugador->setMotion(new \pocketmine\math\Vector3(0, 0.5, 0));
					$jugador->sendTip($this->s . "§fPasaste el primer juego §a:)");
					$this->sonido($jugador,3);
					$this->ganojugadorluz[$jugador->getName()] = true;
				}else{
					if(time() - $this->tiempomoverse > 2 and $jugador->getLevel()->getName() === "01")
					{
						if($lana->getId() === 35 and $lana->getDamage() === 14 or $lana1->getId() === 35 and $lana1->getDamage() === 14 or $lana2->getId() === 35 and $lana2->getDamage() === 14)
						{//xd por si salta mas de 3 bloques
							$l->setCancelled(true);
							$jugador->sendTip($this->s . "Si ya ganaste no puedes devolverte.");
						}
					}
				}
			}
			if(isset($this->luzrojajugadores[$l->getPlayer()->getName()]))
			{
				if(isset($this->pisolineaverde[$jugador->getName()]))
				{//si esta en luz roja el evento pero el jugador no a pisado la linea verde no lo tepeamos
					$this->rayo($jugador,true);//todos lo ven xD
					$jugador->sendMessage($this->s . "§cNo corras con la luz en §cRojo §fcorre antes de que se acabe el tiempo.");
					$jugador->teleport($this->servidor->getDefaultLevel()->getSafeSpawn());
					$this->rayo($jugador,false);//solo el lo ve xD
					unset($this->pisolineaverde[$jugador->getName()]);
				}
			}
			$lanaverde = (($lana->getId() === 35 && $lana->getDamage() === 5) || ($lana1->getId() === 35 && $lana1->getDamage() === 5) || ($lana2->getId() === 35 && $lana2->getDamage() === 5) || ($lana3->getId() === 35 && $lana3->getDamage() === 5)); //xD si pisa la lana verde y no a empezado el evento :v
			if($lanaverde)
			{
				if($this->empezoprimerjuegoluz === false)
				{
					$l->setCancelled(true);
					$jugador->sendTip($this->s. "§fAun no a empezado el juego LUZ §aVERDE §fLUZ §cROJA.");
				}elseif($this->empezoprimerjuegoluz === true)
				{
					$this->pisolineaverde[$jugador->getName()] = true; //si pisa la lana y empezo el evento lo almacenamos xD
				}
			}
		}elseif($jugador->getLevel()->getName() === "07") //camas
		{
			$lana = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 1, 0)); // Y-1 abajo
			$lana1 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 2, 0)); // Y-2 abajo
			$lana2 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 3, 0)); // Y-3 abajo
			$pared = $jugador->getLevel()->getBlock($jugador->floor()->add(0,0,0)); // Y-3 abajo
			if(
			    ($lana->getId() === 35 && $lana->getDamage() === 14) ||
			    ($lana1->getId() === 35 && $lana1->getDamage() === 14) ||
			    ($lana2->getId() === 35 && $lana2->getDamage() === 14)
			)
			{
				if($this->cruzelineascamas !== true)
				{
					$l->setCancelled(true);
					$jugador->sendPopup($this->s . "§fNo puedes pasar a la zona del equipo §8( §1O §8) §faun.");
				}
			}
			elseif(
			    ($lana->getId() === 35 && $lana->getDamage() === 11) ||
			    ($lana1->getId() === 35 && $lana1->getDamage() === 11) ||
			    ($lana2->getId() === 35 && $lana2->getDamage() === 11)
			)
			{
				if($this->cruzelineascamas !== true)
				{
					$l->setCancelled(true);
					$jugador->sendPopup($this->s . "§fNo puedes pasar a la zona del equipo §8( §cX §8) §faun.");
				}
			}
			if($pared->getId() === 44 and $pared->getDamage() === 5 and $this->cruzelineascamas === false)
			{
				$l->setCancelled(true);
				$jugador->sendPopup($this->s . "§fNo puedes pasar por aqui.");
			}
		}elseif($jugador->getLevel()->getName() === "05") //salto puente
		{
			$lana = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 0, 0)); // Y-1 abajo
			$lana1 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 1, 0)); // Y-2 abajo
			$lana2 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 2, 0)); // Y-3 abajo
			$lana3 = $jugador->getLevel()->getBlock($jugador->floor()->subtract(0, 3, 0)); // Y-3 abajo
			$lanaverde = (($lana->getId() === 35 && $lana->getDamage() === 5) || ($lana1->getId() === 35 && $lana1->getDamage() === 5) || ($lana2->getId() === 35 && $lana2->getDamage() === 5) || ($lana3->getId() === 35 && $lana3->getDamage() === 5)); //xD si pisa la lana verde
			$lanaroja = (($lana->getId() === 35 && $lana->getDamage() === 14) || ($lana1->getId() === 35 && $lana1->getDamage() === 14) || ($lana2->getId() === 35 && $lana2->getDamage() === 14) || ($lana3->getId() === 35 && $lana3->getDamage() === 14)); //xD si pisa la lana roja
			if($lanaverde)//piso la linea verde
			{
				if($this->lineaverdepuente === false)
				{
					$l->setCancelled(true);
					$jugador->sendPopup($this->s . "§fNo puedes pasar porque el juego §cPuente Mortal §fno a iniciado.");
				}else{
				    $this->pisolineaverdepuente[$jugador->getName()] = true;
				}
			}
			if($lanaroja)//piso la linea roja
			{
				$jugador->sendTip($this->s . "§fPasaste el segundo juego §a:)");
				$jugador->setNametagVisible(false);
				$this->ganadorpuente[$jugador->getName()] = true;
			}
		}
		if($this->lineaverdepuente === true && !isset($this->ganadorpuente[$jugador->getName()]))
		{
			if(isset($this->agachado[$jugador->getName()]))
			{
			    $motion = $jugador->getMotion();
			    // Reducir velocidad horizontal
			    $motion->x *= 0.05;
			    $motion->z *= 0.05;
			    $jugador->setMotion($motion);
			}else{
			    $motion = $jugador->getMotion();
			    // Reducir velocidad horizontal
			    $motion->x *= 0.1;
			    $motion->z *= 0.1;
			    $jugador->setMotion($motion);
			}
		}
	}

}
